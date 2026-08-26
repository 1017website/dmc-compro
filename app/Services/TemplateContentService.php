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

    public function source(): string
    {
        return file_get_contents(resource_path('templates/dmc-pro.html')) ?: '';
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

    public function render(array $settings = []): string
    {
        $overrides = SiteContent::query()->get()->keyBy('content_key');
        $source = str_replace('var copy = {', 'var copy = window.__dmcCmsCopy = {', $this->source());
        $tokens = $this->tokens($source);
        $state = ['tag' => null, 'group' => 'Umum', 'element' => null, 'elements' => []];
        $textIndex = 0;
        $attributeIndex = 0;
        $attributeAliases = [];
        foreach ($this->fields() as $field) {
            foreach ($field['aliases'] ?? [] as $alias) {
                $attributeAliases[$alias] = $field['key'];
            }
        }

        foreach ($tokens as &$token) {
            if (str_starts_with($token, '<')) {
                $this->updateState($token, $state);
                $token = preg_replace_callback('/\b([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*(["\'])(.*?)\2/s', function (array $match) use (&$attributeIndex, $token, $overrides, $attributeAliases) {
                    if (! $this->isEditableAttribute($token, $match[1], $match[3])) {
                        return $match[0];
                    }
                    $key = sprintf('attr.%04d', ++$attributeIndex);
                    $key = $attributeAliases[$key] ?? $key;
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
            $value = $overrides->get($key)?->value_id;
            if ($value !== null && $value !== '') {
                preg_match('/^\s*/u', $token, $leading);
                preg_match('/\s*$/u', $token, $trailing);
                $token = ($leading[0] ?? '').htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').($trailing[0] ?? '');
            }
        }
        unset($token);

        $html = implode('', $tokens);
        $html = preg_replace('/<footer\b(?![^>]*\bid=)/i', '<footer id="footer"', $html, 1) ?? $html;
        $html = str_replace('Versi demo ini belum mengirim data. Saat dipasang di hosting, formulir dapat diteruskan ke email atau WhatsApp tim DMC Pro.', 'Terima kasih. Permintaan Anda sudah tersimpan dan tim DMC Pro akan segera menindaklanjuti.', $html);
        $html = $this->applyHeroBackground($html, $overrides);
        $html = $this->applySeo($html, $settings);
        $html = $this->applyBranding($html, $settings);
        $html = $this->wireInquiryForm($html);
        $html = str_replace('</body>', $this->clientOverrides($overrides).$this->trackingScripts($settings).'</body>', $html);

        return $html;
    }

    private function tokens(string $html): array
    {
        return preg_split('/(<[^>]+>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
    }

    private function updateState(string $token, array &$state): void
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
            $state['elements'][] = ['tag' => $tag, 'descriptor' => $descriptor];
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
        if ($logo = ($settings['frontend_logo'] ?? null)) {
            $safe = $escape($logo);
            $html = preg_replace('/<img\s+src=(["\']).*?\1\s+alt=(["\'])DMC Pro\2>/s', '<img src="'.$safe.'" alt="DMC Pro">', $html, 1) ?? $html;
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
(function(){var values={$json},dynamic={$dynamicJson};function normalize(field,value){if(field==='bullets')return value.split(/\\r?\\n/).filter(Boolean);if(field==='tags')return value.split(',').map(function(v){return v.trim();}).filter(Boolean);return value;}function patchDynamic(){Object.keys(dynamic).forEach(function(line){Object.keys(dynamic[line]).forEach(function(field){['id','en','zh'].forEach(function(lang){var value=dynamic[line][field][lang];if(!value)return;value=normalize(field,value);if(window.__dmcCmsCopy&&window.__dmcCmsCopy[lang]&&window.__dmcCmsCopy[lang].business.lines[line])window.__dmcCmsCopy[lang].business.lines[line][field]=value;if(lang==='id'&&window.__dmcBaseBusinessLines&&window.__dmcBaseBusinessLines[line])window.__dmcBaseBusinessLines[line][field]=value;});});});}function apply(){var lang=document.body.getAttribute('data-language')||'id',i=0;var walker=document.createTreeWalker(document.documentElement,NodeFilter.SHOW_TEXT,{acceptNode:function(n){if(!n.nodeValue.trim()||['SCRIPT','STYLE'].includes(n.parentElement&&n.parentElement.tagName))return NodeFilter.FILTER_REJECT;return NodeFilter.FILTER_ACCEPT;}});while(walker.nextNode()){i++;if(values[i]&&values[i][lang])walker.currentNode.nodeValue=values[i][lang];}}patchDynamic();document.querySelectorAll('[data-lang]').forEach(function(b){b.addEventListener('click',function(){setTimeout(apply,40);});});setTimeout(apply,0);if(Object.keys(dynamic).length){var active=document.querySelector('[data-business].is-active');if(active)setTimeout(function(){active.click();},10);}})();
</script>
HTML;
    }

    private function dynamicFields(): array
    {
        $labels = [
            'eyebrow' => 'Label kecil', 'title' => 'Judul', 'description' => 'Deskripsi',
            'imageAlt' => 'Deskripsi gambar untuk Google', 'bullets' => 'Daftar poin (satu per baris)', 'tags' => 'Kategori (pisahkan dengan koma)',
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
            foreach ($labels as $field => $label) {
                $default = $defaults[$line][$field] ?? '';
                if (is_array($default)) {
                    $default = implode($field === 'tags' ? ', ' : "\n", $default);
                }
                $fields[] = [
                    'key' => "dynamic.business.{$line}.{$field}",
                    'group' => 'Detail Produk',
                    'label' => "{$lineLabel} · {$label}",
                    'help' => 'Konten detail untuk lini produk '.$lineLabel.'.',
                    'type' => in_array($field, ['description', 'bullets', 'tags'], true) ? 'textarea' : 'text',
                    'default' => $default,
                    'inline_media' => false,
                    'translatable' => true,
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
