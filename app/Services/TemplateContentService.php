<?php

namespace App\Services;

use App\Models\SiteContent;
use App\Models\SiteSetting;
use Illuminate\Support\Str;

class TemplateContentService
{
    private const GROUPS = [
        'partner' => 'Kemitraan', 'tentang' => 'Tentang', 'produk' => 'Produk',
        'layanan' => 'Layanan', 'video' => 'Video Utama', 'video-portfolio' => 'Portofolio Video',
        'galeri' => 'Galeri', 'kontak' => 'Kontak',
    ];

    private ?array $cachedFields = null;

    private ?string $cachedSource = null;

    /**
     * The template is an 8 MB single file and a single page render used to read it
     * three or four times over (render, fields, dynamicFields, media collections),
     * allocating a fresh copy each time. One copy per instance is plenty.
     */
    public function source(): string
    {
        return $this->cachedSource ??= (file_get_contents(resource_path('templates/dmc-pro.html')) ?: '');
    }

    public function fields(): array
    {
        if ($this->cachedFields !== null) {
            return $this->cachedFields;
        }

        $fields = [];
        $textIndex = 0;
        $attributeIndex = 0;
        $state = ['tag' => null, 'group' => 'Umum', 'element' => null, 'elements' => []];
        $mediaFields = [];
        $attributeLabelCounts = [];

        foreach ($this->tokens($this->source()) as $token) {
            if (str_starts_with($token, '<')) {
                $this->updateState($token, $state);
                foreach ($this->attributes($token) as $attribute) {
                    if (! $this->isEditableAttribute($token, $attribute['name'], $attribute['value'])) {
                        continue;
                    }
                    $key = sprintf('attr.%04d', ++$attributeIndex);
                    if (! $this->isVisibleAttribute($attribute['name'])) {
                        continue;
                    }
                    $type = $this->attributeType($token, $attribute['name'], $attribute['value']);
                    if ($attribute['value'] === '') {
                        continue;
                    }
                    $labelCountKey = $state['group'].'|'.$type;
                    $field = [
                        'key' => $key,
                        'group' => $state['group'],
                        'label' => '',
                        'help' => $this->attributeHelp($attribute['name'], $type),
                        'type' => $type,
                        'default' => str_starts_with($attribute['value'], 'data:') ? null : html_entity_decode($attribute['value'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                        'inline_media' => str_starts_with($attribute['value'], 'data:'),
                        'translatable' => false,
                        'aliases' => [],
                        'anchor' => $this->groupAnchor($state['group']),
                    ];

                    if (in_array($type, ['image', 'video'], true) && $attribute['value'] !== '') {
                        $fingerprint = $state['group'].'|'.hash('sha256', $attribute['value']);
                        if (isset($mediaFields[$fingerprint])) {
                            $fields[$mediaFields[$fingerprint]]['aliases'][] = $key;
                            continue;
                        }
                        $mediaFields[$fingerprint] = count($fields);
                    }

                    $ordinal = ($attributeLabelCounts[$labelCountKey] ?? 0) + 1;
                    $attributeLabelCounts[$labelCountKey] = $ordinal;
                    $field['label'] = $this->attributeLabel($attribute['name'], $attribute['value'], $type, $state['group'], $ordinal);

                    $fields[] = $field;
                }
                continue;
            }

            if (in_array($state['tag'], ['style', 'script'], true) || trim($token) === '') {
                continue;
            }

            $plain = html_entity_decode(trim($token), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $key = sprintf('text.%04d', ++$textIndex);
            if (! $this->isVisibleText($plain, $state, $key)) {
                continue;
            }
            $fields[] = [
                'key' => $key,
                'group' => $state['tag'] === 'title' ? 'SEO' : $state['group'],
                'label' => $this->textLabel($plain, $state),
                'help' => $this->textHelp($state),
                'type' => mb_strlen($plain) > 110 ? 'textarea' : 'text',
                'default' => $plain,
                'inline_media' => false,
                'translatable' => true,
                'aliases' => [],
                'anchor' => $this->groupAnchor($state['tag'] === 'title' ? 'SEO' : $state['group']),
            ];
        }

        return $this->cachedFields = [...$fields, ...$this->dynamicFields()];
    }

    public function render(array $settings = [], bool $preview = false): string
    {
        $overrides = SiteContent::query()->get()->keyBy('content_key');
        $source = str_replace('var copy = {', 'var copy = window.__dmcCmsCopy = {', $this->source());
        $tokens = $this->tokens($source);
        unset($source);
        $state = ['tag' => null, 'group' => 'Umum', 'element' => null, 'elements' => []];
        $textIndex = 0;
        $attributeIndex = 0;
        $attributeAliases = [];
        $editableKeys = [];
        foreach ($this->fields() as $field) {
            $editableKeys[$field['key']] = true;
            foreach ($field['aliases'] ?? [] as $alias) {
                $attributeAliases[$alias] = $field['key'];
            }
        }

        // tokenIndex => list of field keys, collected while walking and written back
        // afterwards. Mutating a different element of $tokens mid-iteration would be
        // unsafe, and the opening tag we need is always behind the current position.
        $annotations = [];

        foreach ($tokens as $index => &$token) {
            if (str_starts_with($token, '<')) {
                $this->updateState($token, $state, $index);
                $token = preg_replace_callback('/\b([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*(["\'])(.*?)\2/s', function (array $match) use (&$attributeIndex, &$annotations, $index, $preview, $editableKeys, $token, $overrides, $attributeAliases) {
                    if (! $this->isEditableAttribute($token, $match[1], $match[3])) {
                        return $match[0];
                    }
                    $key = sprintf('attr.%04d', ++$attributeIndex);
                    $key = $attributeAliases[$key] ?? $key;
                    if ($preview && isset($editableKeys[$key])) {
                        $annotations[$index][$key] = true;
                    }
                    $value = $overrides->get($key)?->value_id;
                    if ($value === null || $value === '') {
                        return $match[0];
                    }

                    return $match[1].'='.$match[2].htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').$match[2];
                }, $token) ?? $token;
                continue;
            }

            if (in_array($state['tag'], ['style', 'script'], true) || trim($token) === '') {
                continue;
            }

            $key = sprintf('text.%04d', ++$textIndex);
            if (str_contains((string) ($state['element'] ?? ''), 'modal-close')) {
                continue;
            }
            if ($preview && isset($editableKeys[$key]) && $state['elements'] !== []) {
                $owner = end($state['elements'])['index'] ?? -1;
                if ($owner >= 0) {
                    $annotations[$owner][$key] = true;
                }
            }
            $value = $overrides->get($key)?->value_id;
            if ($value !== null && $value !== '') {
                preg_match('/^\s*/u', $token, $leading);
                preg_match('/\s*$/u', $token, $trailing);
                $token = ($leading[0] ?? '').htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').($trailing[0] ?? '');
            }
        }
        unset($token);

        foreach ($annotations as $index => $keys) {
            $tokens[$index] = $this->annotateTag($tokens[$index], array_keys($keys));
        }

        $html = implode('', $tokens);
        unset($tokens);
        $html = preg_replace('/<footer\b(?![^>]*\bid=)/i', '<footer id="footer"', $html, 1) ?? $html;
        $html = str_replace('Versi demo ini belum mengirim data. Saat dipasang di hosting, formulir dapat diteruskan ke email atau WhatsApp tim DMC Pro.', 'Terima kasih. Permintaan Anda sudah tersimpan dan tim DMC Pro akan segera menindaklanjuti.', $html);
        $html = $this->applyHeroBackground($html, $overrides);
        $html = $this->applyTypography($html);
        $html = $this->applySeo($html, $settings);
        $html = $this->applyBranding($html, $settings);
        $html = $this->wireInquiryForm($html);
        $html = str_replace('</body>', $this->clientOverrides($overrides).$this->mediaCollectionsScript($settings).$this->trackingScripts($settings).'</body>', $html);

        if ($preview) {
            $html = str_replace('</body>', $this->previewBridge().'</body>', $html);
        }

        return $html;
    }

    /**
     * Adds the CMS field keys an element owns to its opening tag, so the editor's
     * preview can translate a click on the page into a form field and back.
     */
    private function annotateTag(string $token, array $keys): string
    {
        if ($keys === [] || ! str_starts_with($token, '<') || str_starts_with($token, '</')) {
            return $token;
        }

        $payload = ' data-cms-key="'.htmlspecialchars(implode(' ', $keys), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"';

        if (preg_match('/\/>$/', $token)) {
            return preg_replace('/\s*\/>$/', $payload.' />', $token, 1) ?? $token;
        }

        return preg_replace('/>$/', $payload.'>', $token, 1) ?? $token;
    }

    public function defaultMediaCollection(string $collection): array
    {
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($this->source(), LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($document);

        if ($collection === 'gallery') {
            $items = [];
            foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " gallery-item ")]') ?: [] as $index => $node) {
                $items[] = [
                    'key' => 'default-'.$index,
                    'source' => 'default',
                    'default_index' => $index,
                    'url' => null,
                    'title' => $node->getAttribute('data-title') ?: 'Foto galeri '.($index + 1),
                    'meta' => $node->getAttribute('data-meta') ?: 'Galeri DMC Pro',
                ];
            }

            return $items;
        }

        if ($collection === 'videos') {
            $items = [];
            foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " portfolio-mockup-card ")]') ?: [] as $index => $node) {
                $text = fn (string $query) => trim((string) $xpath->evaluate('string('.$query.')', $node));
                $items[] = [
                    'key' => 'default-'.$index,
                    'source' => 'default',
                    'default_index' => $index,
                    'url' => null,
                    'category' => $text('.//*[contains(concat(" ", normalize-space(@class), " "), " portfolio-mockup-copy ")]//small[1]'),
                    'title' => $text('.//h3[1]') ?: 'Video '.($index + 1),
                    'description' => $text('.//*[contains(concat(" ", normalize-space(@class), " "), " portfolio-mockup-copy ")]//p[1]'),
                ];
            }

            return $items;
        }

        return [];
    }

    private function tokens(string $html): array
    {
        return preg_split('/(<[^>]+>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
    }

    private function updateState(string $token, array &$state, int $tokenIndex = -1): void
    {
        if (preg_match('/^<\s*\/\s*([a-z0-9]+)/i', $token, $closing)) {
            $closingTag = strtolower($closing[1]);
            if (in_array($closingTag, ['style', 'script', 'title'], true)) {
                $state['tag'] = null;
            }
            if ($closingTag === 'section') {
                $state['group'] = 'Umum';
            }
            while ($state['elements'] !== []) {
                $element = array_pop($state['elements']);
                if ($element['tag'] === $closingTag) {
                    break;
                }
            }
            $state['element'] = $state['elements'] !== [] ? end($state['elements'])['descriptor'] : null;
            return;
        }
        if (! preg_match('/^<\s*([a-z0-9]+)/i', $token, $opening)) {
            return;
        }

        $tag = strtolower($opening[1]);
        $descriptor = '<'.$tag;
        if (preg_match('/\bclass=["\']([^"\']+)["\']/i', $token, $class)) {
            $descriptor .= ' class="'.$class[1].'"';
        }
        if (preg_match('/\baria-hidden=["\']true["\']/i', $token)) {
            $descriptor .= ' aria-hidden="true"';
        }
        if (preg_match('/\bdata-lang=["\'][^"\']+["\']/i', $token)) {
            $descriptor .= ' data-lang="set"';
        }
        $descriptor .= '>';
        if (! in_array($tag, ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'], true)) {
            // The token index lets preview mode annotate the opening tag of whichever
            // element a text field belongs to; text nodes cannot carry attributes.
            $state['elements'][] = ['tag' => $tag, 'descriptor' => $descriptor, 'index' => $tokenIndex];
        }
        $state['element'] = $state['elements'] !== [] ? end($state['elements'])['descriptor'] : $descriptor;
        if (in_array($tag, ['style', 'script', 'title'], true)) {
            $state['tag'] = $tag;
        }
        if (str_contains($token, 'utility-bar')) {
            $state['group'] = 'Header & Navigasi';
        } elseif ($tag === 'header') {
            $state['group'] = 'Header & Navigasi';
        } elseif ($tag === 'footer') {
            $state['group'] = 'Footer';
        } elseif (preg_match('/\bid=["\']video-modal["\']/i', $token)) {
            $state['group'] = 'Video Utama';
        } elseif (preg_match('/\bid=["\']lightbox["\']/i', $token)) {
            $state['group'] = 'Galeri';
        } elseif ($tag === 'section') {
            if (preg_match('/\bid=["\']([^"\']+)["\']/i', $token, $id)) {
                $state['group'] = self::GROUPS[$id[1]] ?? Str::headline($id[1]);
            } elseif (str_contains($token, 'hero')) {
                $state['group'] = 'Hero';
            } elseif (str_contains($token, 'trust-strip')) {
                $state['group'] = 'Keunggulan';
            } elseif (str_contains($token, 'process-section')) {
                $state['group'] = 'Proses';
            }
        }
    }

    private function attributes(string $tag): array
    {
        preg_match_all('/\b([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*(["\'])(.*?)\2/s', $tag, $matches, PREG_SET_ORDER);
        return array_map(fn (array $match) => ['name' => strtolower($match[1]), 'value' => $match[3]], $matches);
    }

    private function isEditableAttribute(string $tag, string $name, string $value): bool
    {
        $name = strtolower($name);
        $lowerTag = strtolower($tag);
        if (($name === 'href' && str_contains($lowerTag, 'rel="icon"'))
            || (in_array($name, ['src', 'alt'], true) && preg_match('/\balt=["\']DMC Pro["\']/i', $tag))) {
            return false;
        }
        // The product panel image is reassigned by renderBusiness() from
        // window.__dmcBaseBusinessLines on every render, so editing the markup here
        // has no lasting effect. It is edited through dynamic.business.*.image instead.
        if (in_array($name, ['src', 'alt'], true) && str_contains($lowerTag, 'id="business-image"')) {
            return false;
        }
        if ($name === 'content') {
            return str_contains($lowerTag, 'name="description"') || str_contains($lowerTag, "name='description'");
        }

        return in_array($name, ['href', 'src', 'poster', 'alt', 'data-src', 'data-alt', 'data-meta', 'data-title', 'placeholder', 'aria-label'], true)
            && ! ($name === 'href' && str_starts_with($value, '#'));
    }

    private function isVisibleAttribute(string $name): bool
    {
        return ! in_array(strtolower($name), ['content', 'aria-label', 'data-alt', 'data-meta', 'data-title', 'alt'], true);
    }

    private function isVisibleText(string $plain, array $state, string $key): bool
    {
        $element = strtolower((string) ($state['element'] ?? ''));

        // The video play buttons are icon-only by design. Their accessible name comes
        // from the button's own aria-label, so this caption is hidden in CSS and must
        // not be offered as an editable field.
        foreach ($state['elements'] ?? [] as $ancestor) {
            if (str_contains(strtolower((string) ($ancestor['descriptor'] ?? '')), 'portfolio-play')) {
                return false;
            }
        }

        if (in_array($key, [
            'text.0038', 'text.0039',
            'text.0081', 'text.0082',
            'text.0083', 'text.0084', 'text.0085', 'text.0086', 'text.0087',
            'text.0089', 'text.0091', 'text.0093', 'text.0095', 'text.0096',
            'text.0097', 'text.0098', 'text.0099', 'text.0100', 'text.0101',
            'text.0251', 'text.0252',
        ], true)) {
            return false;
        }

        if (($state['tag'] ?? null) === 'title'
            || str_contains($element, 'aria-hidden="true"')
            || str_contains($element, "aria-hidden='true'")
            || str_contains($element, 'data-lang=')) {
            return false;
        }

        if (preg_match('/^[↗↓▶✓+×→%]+$/u', $plain)
            || preg_match('/^0[1-4]$/', $plain)
            || preg_match('/^\d{2}:\d{2}$/', $plain)
            || preg_match('/^\d{2}\s*\/\s*\d{2}$/', $plain)
            || preg_match('/^DMC\s*\/\s*\d+$/i', $plain)) {
            return false;
        }

        return ! in_array($plain, [
            'with', 'Browser Anda belum mendukung video HTML5.', 'DMC', 'PRO',
        ], true);
    }

    private function attributeType(string $tag, string $name, string $value): string
    {
        if ($name === 'poster' || str_starts_with($value, 'data:image') || (in_array($name, ['src', 'data-src'], true) && preg_match('/^<\s*img\b/i', $tag))) {
            return 'image';
        }
        if (str_starts_with($value, 'data:video') || (in_array($name, ['src', 'poster'], true) && preg_match('/<(video|source)\b/i', $tag))) {
            return 'video';
        }
        if (in_array($name, ['href', 'src'], true)) {
            return 'url';
        }

        return 'text';
    }

    private function attributeLabel(string $name, string $value, string $type, string $group, int $ordinal): string
    {
        if ($group === 'Galeri' && $type === 'image') {
            return 'Foto galeri '.$ordinal;
        }
        if ($group === 'Video Utama' && $type === 'image') {
            return 'Sampul video';
        }
        if ($group === 'Video Utama' && $type === 'video') {
            return 'Video company profile';
        }
        if ($type === 'image') {
            return 'Gambar '.$ordinal.' di bagian '.$group;
        }
        if ($type === 'video') {
            return 'Video '.$ordinal.' di bagian '.$group;
        }
        if ($name === 'href' && str_starts_with($value, 'tel:')) {
            return 'Nomor telepon yang dituju';
        }
        if ($name === 'href') {
            return 'Alamat tautan — '.Str::limit($value, 48);
        }
        if ($name === 'placeholder') {
            return 'Contoh isian — '.Str::limit(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'), 48);
        }

        return 'Alamat media atau tautan';
    }

    private function attributeHelp(string $name, string $type): string
    {
        return match (true) {
            $type === 'video' => 'Pilih file video dari perangkat, atau gunakan URL langsung menuju file video.',
            $type === 'image' => 'Pilih foto dari perangkat, atau gunakan URL langsung menuju file gambar.',
            $name === 'href' => 'Alamat yang dibuka ketika pengunjung mengeklik tautan.',
            $name === 'placeholder' => 'Contoh teks yang terlihat sebelum pengunjung mengisi kolom.',
            default => 'Isi yang tampil pada website.',
        };
    }

    private function textLabel(string $plain, array $state): string
    {
        $element = strtolower((string) ($state['element'] ?? ''));
        $tag = preg_match('/^<\s*([a-z0-9]+)/i', $element, $match) ? strtolower($match[1]) : '';
        $prefix = match (true) {
            str_contains($element, 'eyebrow'), str_contains($element, 'panel-kicker') => 'Label kecil',
            $tag === 'h1' => 'Judul utama',
            $tag === 'h2' => 'Judul bagian',
            in_array($tag, ['h3', 'h4'], true) => 'Judul item',
            $tag === 'em' => 'Judul utama (teks yang disorot)',
            $tag === 'blockquote' => 'Kutipan komitmen',
            $tag === 'p' => 'Deskripsi',
            $tag === 'a' => 'Teks tautan',
            $tag === 'button' => 'Teks tombol',
            $tag === 'option' => 'Pilihan formulir',
            $tag === 'label' => 'Nama kolom formulir',
            $tag === 'small' => 'Keterangan singkat',
            $tag === 'strong' => 'Teks utama',
            default => 'Teks',
        };
        $preview = Str::limit(preg_replace('/\s+/u', ' ', strip_tags($plain)), 56);

        return $prefix.' — '.$preview;
    }

    private function textHelp(array $state): string
    {
        $element = strtolower((string) ($state['element'] ?? ''));

        return match (true) {
            str_contains($element, 'eyebrow'), str_contains($element, 'panel-kicker') => 'Teks kecil yang tampil di atas judul.',
            preg_match('/^<\s*h[1-4]\b/i', $element) === 1 => 'Judul yang menonjol pada bagian ini.',
            preg_match('/^<\s*(a|button)\b/i', $element) === 1 => 'Teks yang terlihat pada tombol atau tautan.',
            preg_match('/^<\s*p\b/i', $element) === 1 => 'Kalimat penjelas yang dibaca pengunjung.',
            default => 'Ubah teks yang terlihat pada website.',
        };
    }

    /**
     * Runs only inside the CMS editor iframe. Turns the live page into a picker: a
     * click reports the field keys of whatever was clicked, and the editor can send
     * back highlight requests and unsaved values for an instant preview.
     */
    private function previewBridge(): string
    {
        $script = <<<'JS'
<style>
[data-cms-key]{cursor:pointer}
[data-cms-key]:hover{outline:2px dashed rgba(227,25,25,.65);outline-offset:2px}
.dmc-cms-active{outline:3px solid #e31919 !important;outline-offset:3px;transition:outline-color 140ms ease}
</style>
<script>
(function(){
 'use strict';
 var ORIGIN=window.location.origin;
 function post(payload){payload.source='dmc-cms-preview';try{window.parent.postMessage(payload,ORIGIN);}catch(e){}}
 function clearActive(){Array.prototype.forEach.call(document.querySelectorAll('.dmc-cms-active'),function(n){n.classList.remove('dmc-cms-active');});}
 function mark(node){clearActive();node.classList.add('dmc-cms-active');}

 // Overlays would cover the page the editor is trying to show, so they stay shut
 // here. Product tabs are deliberately left working — switching lines is the only
 // way to preview the second product panel.
 document.addEventListener('click',function(event){
  var blocked=event.target.closest('.gallery-item,.js-open-video,.js-portfolio-play');
  if(blocked){event.preventDefault();event.stopPropagation();}
  var link=event.target.closest('a[href]');
  if(link){var href=link.getAttribute('href')||'';if(href&&href.charAt(0)!=='#')event.preventDefault();}
  var owner=event.target.closest('[data-cms-key]');
  if(!owner)return;
  mark(owner);
  post({type:'pick',keys:(owner.getAttribute('data-cms-key')||'').split(/\s+/).filter(Boolean)});
 },true);

 var nodes=null,baseline=[];
 function collect(){
  var list=[],walker=document.createTreeWalker(document.documentElement,NodeFilter.SHOW_TEXT,{acceptNode:function(n){
   if(!n.nodeValue.trim()||['SCRIPT','STYLE'].includes(n.parentElement&&n.parentElement.tagName))return NodeFilter.FILTER_REJECT;
   return NodeFilter.FILTER_ACCEPT;}});
  while(walker.nextNode())list.push(walker.currentNode);
  return list;
 }
 function ensure(){if(!nodes){nodes=collect();baseline=nodes.map(function(n){return n.nodeValue;});}}

 // Text fields are numbered by document order on the server, so the nth accepted
 // text node is the nth key. An empty box previews the last saved wording again.
 window.__dmcCmsSetText=function(key,value){
  ensure();
  var index=parseInt(String(key).split('.')[1],10);
  var node=nodes[index-1];
  if(!node)return;
  node.nodeValue=(value===''||value==null)?baseline[index-1]:value;
 };

 window.__dmcCmsSetDynamic=function(line,field,value){
  var base=window.__dmcBaseBusinessLines&&window.__dmcBaseBusinessLines[line];
  if(!base)return;
  var parsed=value;
  if(field==='bullets')parsed=String(value).split(/\r?\n/).filter(Boolean);
  if(field==='tags')parsed=String(value).split(',').map(function(v){return v.trim();}).filter(Boolean);
  base[field]=parsed;
  ['id','en','zh'].forEach(function(lang){
   var copy=window.__dmcCmsCopy&&window.__dmcCmsCopy[lang]&&window.__dmcCmsCopy[lang].business&&window.__dmcCmsCopy[lang].business.lines[line];
   if(copy)copy[field]=parsed;
  });
  var tab=document.querySelector('[data-business="'+line+'"]');
  if(tab)tab.click();
 };

 window.addEventListener('message',function(event){
  if(event.origin!==ORIGIN)return;
  var data=event.data;
  if(!data||data.source!=='dmc-cms-editor')return;
  if(data.type==='focus'){
   var node=document.querySelector('[data-cms-key~="'+data.key+'"]');
   if(!node)return;
   mark(node);
   node.scrollIntoView({behavior:'smooth',block:'center'});
   return;
  }
  if(data.type==='text')window.__dmcCmsSetText(data.key,data.value);
  if(data.type==='dynamic')window.__dmcCmsSetDynamic(data.line,data.field,data.value);
 });

 window.setTimeout(function(){ensure();post({type:'ready'});},400);
})();
</script>
JS;

        return $script;
    }

    /**
     * The webfont is injected here instead of being written into the template head
     * on purpose: a <link href> inside the template would be picked up as an
     * editable attribute, adding a meaningless field to the CMS and shifting every
     * attr.* index that follows it. The template only declares the family names.
     *
     * The variable axis matters — the stylesheet asks for weights 430, 550, 570,
     * 590, 650, 680, 730 and 760, which only resolve against a variable font.
     */
    private function applyTypography(string $html): string
    {
        $fonts = '<link rel="preconnect" href="https://fonts.googleapis.com">'
            .'<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
            .'<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200..800&display=swap">';

        return str_replace('</head>', $fonts."\n</head>", $html);
    }

    private function applySeo(string $html, array $settings): string
    {
        $esc = fn (?string $value) => htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if ($title = ($settings['seo_title'] ?? null)) {
            $html = preg_replace('/<title>.*?<\/title>/s', '<title>'.$esc($title).'</title>', $html, 1) ?? $html;
        }
        if ($description = ($settings['seo_description'] ?? null)) {
            $replacement = '<meta name="description" content="'.$esc($description).'">';
            $html = preg_replace('/<meta\s+name="description"\s+content=".*?">/s', $replacement, $html, 1) ?? $html;
        }

        $seo = [];
        if ($keywords = ($settings['seo_keywords'] ?? null)) $seo[] = '<meta name="keywords" content="'.$esc($keywords).'">';
        if ($robots = ($settings['seo_robots'] ?? null)) $seo[] = '<meta name="robots" content="'.$esc($robots).'">';
        if ($canonical = ($settings['canonical_url'] ?? null)) $seo[] = '<link rel="canonical" href="'.$esc($canonical).'">';
        $ogTitle = $settings['og_title'] ?? $settings['seo_title'] ?? null;
        $ogDescription = $settings['og_description'] ?? $settings['seo_description'] ?? null;
        if ($ogTitle) $seo[] = '<meta property="og:title" content="'.$esc($ogTitle).'">';
        if ($ogDescription) $seo[] = '<meta property="og:description" content="'.$esc($ogDescription).'">';
        if ($ogImage = ($settings['og_image'] ?? null)) $seo[] = '<meta property="og:image" content="'.$esc($ogImage).'">';
        $seo[] = '<meta property="og:type" content="website">';
        $seo[] = '<meta name="twitter:card" content="summary_large_image">';
        $seo[] = '<meta name="csrf-token" content="'.csrf_token().'">';

        return str_replace('</head>', implode("\n", $seo)."\n</head>", $html);
    }

    private function applyBranding(string $html, array $settings): string
    {
        $escape = fn (string $value) => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $footerLogo = null;
        if ($logo = ($settings['frontend_logo'] ?? null)) {
            $safe = $escape($logo);
            $html = preg_replace('/<img\s+src=(["\']).*?\1\s+alt=(["\'])DMC Pro\2>/s', '<img src="'.$safe.'" alt="DMC Pro">', $html, 1) ?? $html;
            $footerLogo = $safe;
        } elseif (preg_match('/<a\s+class=(["\'])brand\1[^>]*>\s*<img\s+src=(["\'])(.*?)\2\s+alt=(["\'])DMC Pro\4>/s', $html, $match)) {
            $footerLogo = $match[3];
        }
        if ($footerLogo !== null) {
            $html = preg_replace_callback(
                '/<div\s+class=(["\'])footer-brand\1>(.*?)<\/div>/s',
                function (array $match) use ($footerLogo) {
                    // Only the lettermark is swapped for the uploaded logo. The company
                    // line underneath has its own styling (.footer-brand p) and owns a
                    // CMS field, so dropping it both broke the design and made that
                    // field edit nothing.
                    preg_match('/<p\b[^>]*>.*?<\/p>/s', $match[2], $caption);

                    return '<div class="footer-brand"><img src="'.$footerLogo.'" alt="DMC Pro">'.($caption[0] ?? '').'</div>';
                },
                $html,
                1,
            ) ?? $html;
        }
        if ($favicon = ($settings['favicon'] ?? null)) {
            $safe = $escape($favicon);
            $html = preg_replace('/<link\s+rel=(["\'])icon\1\s+href=(["\']).*?\2>/s', '<link rel="icon" href="'.$safe.'">', $html, 1) ?? $html;
        }

        return $html;
    }

    private function wireInquiryForm(string $html): string
    {
        $html = preg_replace('/<form class="inquiry-form" id="inquiry-form">/', '<form class="inquiry-form" id="inquiry-form" method="post" action="'.route('inquiries.store').'">'.csrf_field(), $html, 1) ?? $html;
        $script = <<<'JS'
<script>
(function(){var form=document.getElementById('inquiry-form');if(!form)return;form.addEventListener('submit',function(){fetch(form.action,{method:'POST',body:new FormData(form),headers:{'Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}}).then(function(response){if(!response.ok)throw new Error();return response.json();}).then(function(){if(typeof gtag==='function'&&window.__dmcGoogleAdsConversion)gtag('event','conversion',{send_to:window.__dmcGoogleAdsConversion});if(typeof fbq==='function')fbq('track','Lead');}).catch(function(){document.getElementById('form-fields').hidden=false;document.getElementById('form-success').hidden=true;alert('Permintaan belum dapat dikirim. Silakan periksa data dan coba kembali.');});});})();
</script>
JS;

        return str_replace('</body>', $script.'</body>', $html);
    }

    private function clientOverrides($overrides): string
    {
        $values = [];
        $dynamic = [];
        foreach ($overrides as $key => $item) {
            if (str_starts_with($key, 'text.')) {
                $index = (int) substr($key, 5);
                $values[$index] = ['id' => $item->value_id, 'en' => $item->value_en, 'zh' => $item->value_zh];
            } elseif (str_starts_with($key, 'dynamic.business.')) {
                [, , $line, $field] = explode('.', $key, 4);
                $dynamic[$line][$field] = ['id' => $item->value_id, 'en' => $item->value_en, 'zh' => $item->value_zh];
            }
        }
        $json = json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
        $dynamicJson = json_encode($dynamic, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);

        return <<<HTML
<script>
(function(){var values={$json},dynamic={$dynamicJson};function normalize(field,value){if(field==='bullets')return value.split(/\\r?\\n/).filter(Boolean);if(field==='tags')return value.split(',').map(function(v){return v.trim();}).filter(Boolean);return value;}function patchDynamic(){Object.keys(dynamic).forEach(function(line){Object.keys(dynamic[line]).forEach(function(field){['id','en','zh'].forEach(function(lang){var value=dynamic[line][field][lang];if(!value)return;value=normalize(field,value);if(window.__dmcCmsCopy&&window.__dmcCmsCopy[lang]&&window.__dmcCmsCopy[lang].business.lines[line])window.__dmcCmsCopy[lang].business.lines[line][field]=value;if(lang==='id'&&window.__dmcBaseBusinessLines&&window.__dmcBaseBusinessLines[line])window.__dmcBaseBusinessLines[line][field]=value;});});});}function apply(){var lang=document.body.getAttribute('data-language')||'id',i=0;var walker=document.createTreeWalker(document.documentElement,NodeFilter.SHOW_TEXT,{acceptNode:function(n){if(!n.nodeValue.trim()||['SCRIPT','STYLE'].includes(n.parentElement&&n.parentElement.tagName))return NodeFilter.FILTER_REJECT;return NodeFilter.FILTER_ACCEPT;}});while(walker.nextNode()){i++;if(values[i]&&values[i][lang])walker.currentNode.nodeValue=values[i][lang];}}function paintActiveBusiness(){var active=document.querySelector('[data-business].is-active');var key=active?active.getAttribute('data-business'):'salt';var base=window.__dmcBaseBusinessLines&&window.__dmcBaseBusinessLines[key];if(!base)return;var image=document.getElementById('business-image');if(image&&base.image&&image.getAttribute('src')!==base.image)image.src=base.image;var share=document.getElementById('business-share');if(share&&base.share)share.textContent=base.share;}patchDynamic();paintActiveBusiness();document.querySelectorAll('[data-lang]').forEach(function(b){b.addEventListener('click',function(){setTimeout(apply,40);});});setTimeout(apply,0);if(Object.keys(dynamic).length){var active=document.querySelector('[data-business].is-active');if(active)setTimeout(function(){active.click();},10);}})();
</script>
HTML;
    }

    private function dynamicFields(): array
    {
        // The product panel is rebuilt by the template's own renderBusiness() on load
        // and on every tab click, reading from window.__dmcBaseBusinessLines. Anything
        // shown there has to be edited through these dynamic keys — a plain attr.*/text.*
        // override gets overwritten by that script a moment after the page paints.
        $labels = [
            'eyebrow' => ['label' => 'Label kecil', 'type' => 'text', 'translatable' => true],
            'title' => ['label' => 'Judul', 'type' => 'text', 'translatable' => true],
            'description' => ['label' => 'Deskripsi', 'type' => 'textarea', 'translatable' => true],
            'image' => ['label' => 'Foto produk', 'type' => 'image', 'translatable' => false],
            'imageAlt' => ['label' => 'Deskripsi gambar untuk Google', 'type' => 'text', 'translatable' => true],
            'share' => ['label' => 'Angka porsi portofolio', 'type' => 'text', 'translatable' => false],
            'bullets' => ['label' => 'Daftar poin (satu per baris)', 'type' => 'textarea', 'translatable' => true],
            'tags' => ['label' => 'Kategori (pisahkan dengan koma)', 'type' => 'textarea', 'translatable' => true],
        ];
        $lines = ['salt' => 'Garam Industri', 'chemical' => 'Bahan Baku Kimia untuk Industri'];
        $defaults = [];
        if (preg_match('/window\.__dmcBaseBusinessLines\s*=\s*(\{.*?\});\s*var businessLines/s', $this->source(), $match)) {
            $defaults = json_decode($match[1], true) ?: [];
        }
        if (isset($defaults['chemical'])) {
            $defaults['chemical']['eyebrow'] = '50% Portofolio · Bahan Baku Kimia untuk Industri';
        }
        $fields = [[
            'key' => 'dynamic.hero.background',
            'group' => 'Hero',
            'label' => 'Background utama Hero',
            'help' => 'Upload foto utama yang tampil memenuhi area paling atas website. Rekomendasi ukuran 1920 × 1080 piksel.',
            'type' => 'image',
            'default' => null,
            'inline_media' => false,
            'translatable' => false,
            'aliases' => [],
            'anchor' => '#top',
        ]];
        foreach ($lines as $line => $lineLabel) {
            foreach ($labels as $field => $meta) {
                $default = $defaults[$line][$field] ?? '';
                if (is_array($default)) {
                    $default = implode($field === 'tags' ? ', ' : "\n", $default);
                }
                // The built-in product photos are inline base64, which must never be
                // poured into a text input as a default value.
                if ($meta['type'] === 'image') {
                    $default = null;
                }
                $fields[] = [
                    'key' => "dynamic.business.{$line}.{$field}",
                    'group' => 'Detail Produk',
                    'label' => "{$lineLabel} · {$meta['label']}",
                    'help' => $meta['type'] === 'image'
                        ? 'Foto besar yang tampil di panel '.$lineLabel.'. Rekomendasi 1200 × 1500 piksel (potret).'
                        : ($field === 'share'
                            ? 'Angka yang tampil pada badge porsi portofolio, misalnya "50%".'
                            : 'Konten detail untuk lini produk '.$lineLabel.'.'),
                    'type' => $meta['type'],
                    'default' => $default,
                    'inline_media' => false,
                    'translatable' => $meta['translatable'],
                    'aliases' => [],
                    'anchor' => '#produk',
                ];
            }
        }
        return $fields;
    }

    private function applyHeroBackground(string $html, $overrides): string
    {
        $background = trim((string) ($overrides->get('dynamic.hero.background')?->value_id ?? ''));
        if ($background === '') {
            return $html;
        }

        $cssUrl = str_replace(['\\', '"'], ['\\\\', '\\"'], $background);
        $style = htmlspecialchars(
            'background-image: linear-gradient(90deg, rgba(0, 0, 0, 0.08), transparent 45%), url("'.$cssUrl.'");',
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );

        return preg_replace(
            '/<div class="hero-media"/',
            '<div class="hero-media" style="'.$style.'"',
            $html,
            1,
        ) ?? $html;
    }

    private function groupAnchor(string $group): string
    {
        return match ($group) {
            'Header & Navigasi', 'Hero', 'SEO', 'Umum' => '#top',
            'Keunggulan' => '#partner',
            'Kemitraan' => '#partner',
            'Tentang' => '#tentang',
            'Produk', 'Detail Produk' => '#produk',
            'Layanan' => '#layanan',
            'Video Utama' => '#video',
            'Portofolio Video' => '#video-portfolio',
            'Galeri' => '#galeri',
            'Proses' => '#kontak',
            'Kontak' => '#kontak',
            'Footer' => '#footer',
            default => '#top',
        };
    }

    private function mediaCollectionsScript(array $settings): string
    {
        $collections = [];
        foreach (['gallery', 'videos'] as $name) {
            $key = 'media_collection_'.$name;
            if (! array_key_exists($key, $settings)) {
                continue;
            }
            $decoded = json_decode((string) $settings[$key], true);
            $collections[$name] = is_array($decoded) ? array_values($decoded) : [];
        }
        if ($collections === []) {
            return '';
        }

        $json = json_encode($collections, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);

        return <<<HTML
<style>
.gallery-grid.has-dynamic-items{grid-template-columns:repeat(auto-fit,minmax(260px,1fr));grid-template-rows:none;grid-auto-rows:310px}.gallery-grid.has-dynamic-items .gallery-item{grid-column:auto}.portfolio-mockup-grid.has-dynamic-items{grid-template-columns:repeat(auto-fit,minmax(280px,1fr))}.portfolio-mockup-grid.has-dynamic-items .portfolio-mockup-card:not(.is-featured){grid-column:auto}.portfolio-thumbnail video{width:100%;height:100%;object-fit:cover;transition:transform 600ms cubic-bezier(.2,.8,.2,1);pointer-events:none}.portfolio-thumbnail:hover video{transform:scale(1.035)}.media-collection-empty{grid-column:1/-1;padding:42px;border:1px dashed rgba(0,0,0,.2);color:#777;text-align:center}.portfolio-mockup-grid .media-collection-empty{color:rgba(255,255,255,.64);background:#171717}
</style>
<script>
(function(){
 'use strict';
 var collections={$json};
 function openGallery(item){var modal=document.getElementById('lightbox'),image=document.getElementById('lightbox-image');if(!modal||!image)return;image.src=item.url;image.alt=item.title||'Foto galeri';document.getElementById('lightbox-meta').textContent=item.meta||'';document.getElementById('lightbox-title').textContent=item.title||'';modal.hidden=false;document.body.style.overflow='hidden';}
 if(Object.prototype.hasOwnProperty.call(collections,'gallery')){
  var galleryGrid=document.querySelector('.gallery-grid'),galleryOriginals=galleryGrid?Array.prototype.map.call(galleryGrid.querySelectorAll('.gallery-item'),function(node){return node.cloneNode(true);}):[];
  if(galleryGrid){galleryGrid.classList.add('has-dynamic-items');var galleryNodes=collections.gallery.map(function(item,index){var node=(galleryOriginals[item.default_index]||galleryOriginals[0]);if(!node)return null;node=node.cloneNode(true);node.className='gallery-item gallery-item-'+((index%6)+1);var image=node.querySelector('img'),url=item.url||(image&&image.src)||node.dataset.src;node.dataset.src=url;node.dataset.title=item.title||'';node.dataset.meta=item.meta||'';node.dataset.alt=item.title||'Foto galeri';node.setAttribute('aria-label',item.title||'Foto galeri');if(image){image.src=url;image.alt=item.title||'Foto galeri';}var small=node.querySelector('.gallery-overlay small'),strong=node.querySelector('.gallery-overlay strong');if(small)small.textContent=item.meta||'';if(strong)strong.textContent=item.title||'';node.addEventListener('click',function(){openGallery({url:url,title:item.title,meta:item.meta});});return node;}).filter(Boolean);galleryGrid.replaceChildren.apply(galleryGrid,galleryNodes.length?galleryNodes:[Object.assign(document.createElement('div'),{className:'media-collection-empty',textContent:'Belum ada foto galeri.'})]);}
 }
 if(Object.prototype.hasOwnProperty.call(collections,'videos')){
  var videoGrid=document.querySelector('.portfolio-mockup-grid'),videoOriginals=videoGrid?Array.prototype.map.call(videoGrid.querySelectorAll('.portfolio-mockup-card'),function(node){return node.cloneNode(true);}):[],modalVideo=document.getElementById('modal-video'),defaultVideo=modalVideo?(modalVideo.currentSrc||modalVideo.querySelector('source')&&modalVideo.querySelector('source').src||''):'';
  if(videoGrid){videoGrid.classList.add('has-dynamic-items');var videoNodes=collections.videos.map(function(item,index){var node=(videoOriginals[item.default_index]||videoOriginals[0]);if(!node)return null;node=node.cloneNode(true);node.classList.toggle('is-featured',index===0);node.dataset.portfolioCard=String(index);var number=node.querySelector('.portfolio-card-number'),category=node.querySelector('.portfolio-mockup-copy small'),title=node.querySelector('h3'),description=node.querySelector('.portfolio-mockup-copy p'),button=node.querySelector('.js-portfolio-play'),image=node.querySelector('img'),poster=image&&image.src,duration=node.querySelector('.portfolio-card-meta span:last-child');if(number)number.textContent=String(index+1).padStart(2,'0');if(category)category.textContent=item.category||'Video';if(title)title.textContent=item.title||('Video '+(index+1));if(description)description.textContent=item.description||'';if(image&&item.poster_url){image.src=item.poster_url;poster=item.poster_url;}else if(image&&item.source!=='default'&&item.url){var preview=document.createElement('video');preview.src=item.url;preview.muted=true;preview.preload='metadata';preview.playsInline=true;preview.setAttribute('aria-hidden','true');preview.addEventListener('loadedmetadata',function(){if(duration&&isFinite(preview.duration)){var seconds=Math.round(preview.duration);duration.textContent=String(Math.floor(seconds/60)).padStart(2,'0')+':'+String(seconds%60).padStart(2,'0');}});image.replaceWith(preview);}if(button){button.setAttribute('aria-label','Putar · '+(item.title||'Video'));button.addEventListener('click',function(){if(!modalVideo)return;modalVideo.pause();modalVideo.src=item.url||defaultVideo;if(poster)modalVideo.poster=poster;var modal=document.getElementById('video-modal');modal.hidden=false;document.body.style.overflow='hidden';modalVideo.load();var result=modalVideo.play();if(result&&typeof result.catch==='function')result.catch(function(){});});}return node;}).filter(Boolean);videoGrid.replaceChildren.apply(videoGrid,videoNodes.length?videoNodes:[Object.assign(document.createElement('div'),{className:'media-collection-empty',textContent:'Belum ada video portofolio.'})]);var count=document.querySelector('.portfolio-heading-copy strong');if(count)count.textContent=collections.videos.length+' Video Portfolio · DMC Pro 2026';}
 }
 function restoreCollectionCopy(){if(collections.gallery)document.querySelectorAll('.gallery-item').forEach(function(node,index){var item=collections.gallery[index];if(!item)return;node.dataset.title=item.title||'';node.dataset.meta=item.meta||'';node.setAttribute('aria-label',item.title||'Foto galeri');var small=node.querySelector('.gallery-overlay small'),strong=node.querySelector('.gallery-overlay strong');if(small)small.textContent=item.meta||'';if(strong)strong.textContent=item.title||'';});if(collections.videos)document.querySelectorAll('[data-portfolio-card]').forEach(function(node,index){var item=collections.videos[index];if(!item)return;var category=node.querySelector('.portfolio-mockup-copy small'),title=node.querySelector('h3'),description=node.querySelector('.portfolio-mockup-copy p');if(category)category.textContent=item.category||'Video';if(title)title.textContent=item.title||('Video '+(index+1));if(description)description.textContent=item.description||'';});var count=document.querySelector('.portfolio-heading-copy strong');if(count&&collections.videos)count.textContent=collections.videos.length+' Video Portfolio · DMC Pro 2026';}
 document.querySelectorAll('[data-lang]').forEach(function(button){button.addEventListener('click',function(){window.setTimeout(restoreCollectionCopy,60);});});
 window.setTimeout(restoreCollectionCopy,60);
 if(modalVideo)document.querySelectorAll('.js-open-video').forEach(function(button){if(button.closest('[data-portfolio-card]'))return;button.addEventListener('click',function(){modalVideo.pause();modalVideo.src=defaultVideo;modalVideo.load();},{capture:true});});
})();
</script>
HTML;
    }

    private function trackingScripts(array $settings): string
    {
        $scripts = '';
        $ga = trim($settings['google_analytics_id'] ?? '');
        $ads = trim($settings['google_ads_id'] ?? '');
        if (preg_match('/^(G|AW)-[A-Z0-9-]+$/i', $ga ?: $ads)) {
            $id = $ga ?: $ads;
            $safe = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
            $configs = array_filter([$ga, $ads]);
            $configJs = implode('', array_map(fn ($item) => "gtag('config', ".json_encode($item).');', array_unique($configs)));
            $scripts .= "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$safe}\"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());{$configJs}</script>";
        }
        $label = trim($settings['google_ads_conversion_label'] ?? '');
        if ($ads && $label && preg_match('/^AW-[0-9]+$/i', $ads) && preg_match('/^[A-Za-z0-9_-]+$/', $label)) {
            $scripts .= '<script>window.__dmcGoogleAdsConversion='.json_encode($ads.'/'.$label).';</script>';
        }
        $pixel = trim($settings['meta_pixel_id'] ?? '');
        if (preg_match('/^\d{5,30}$/', $pixel)) {
            $id = json_encode($pixel);
            $scripts .= "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init',{$id});fbq('track','PageView');</script>";
        }

        return $scripts;
    }
}
