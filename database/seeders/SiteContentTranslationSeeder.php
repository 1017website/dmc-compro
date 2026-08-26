<?php

namespace Database\Seeders;

use App\Models\SiteContent;
use App\Services\TemplateContentService;
use Illuminate\Database\Seeder;

class SiteContentTranslationSeeder extends Seeder
{
    public function run(TemplateContentService $template): void
    {
        $fields = collect($template->fields())->keyBy('key');

        foreach ($this->translations() as $key => $translation) {
            $field = $fields->get($key);
            if (! $field || ! ($field['translatable'] ?? true)) {
                continue;
            }

            $content = SiteContent::query()->firstOrNew(['content_key' => $key]);
            $content->group_name = $field['group'];
            $content->label = $field['label'];
            $content->type = $field['type'];

            foreach (['id', 'en', 'zh'] as $language) {
                $column = 'value_'.$language;
                if ($content->{$column} === null || $content->{$column} === '') {
                    $content->{$column} = $translation[$language];
                }
            }

            $content->save();
        }
    }

    private function translations(): array
    {
        return json_decode(<<<'JSON'
{
  "text.0002": {
    "id": "PT. Dynamika Multi Compro",
    "en": "PT. Dynamika Multi Compro",
    "zh": "PT. Dynamika Multi Compro"
  },
  "text.0003": {
    "id": "Pasokan Domestik & Ekspor",
    "en": "Domestic & Export Supply",
    "zh": "国内与出口供应"
  },
  "text.0004": {
    "id": "031 · 5057662",
    "en": "031 · 5057662",
    "zh": "031 · 5057662"
  },
  "text.0008": {
    "id": "Kemitraan",
    "en": "Partnership",
    "zh": "合作关系"
  },
  "text.0009": {
    "id": "Tentang",
    "en": "About",
    "zh": "关于我们"
  },
  "text.0010": {
    "id": "Produk",
    "en": "Products",
    "zh": "产品"
  },
  "text.0011": {
    "id": "Layanan",
    "en": "Services",
    "zh": "服务"
  },
  "text.0012": {
    "id": "Video",
    "en": "Video",
    "zh": "视频"
  },
  "text.0013": {
    "id": "Galeri",
    "en": "Gallery",
    "zh": "图库"
  },
  "text.0014": {
    "id": "Kontak",
    "en": "Contact",
    "zh": "联系"
  },
  "text.0018": {
    "id": "Minta Penawaran",
    "en": "Request a Quote",
    "zh": "索取报价"
  },
  "text.0020": {
    "id": "Business Partner · PT Garam (Persero)",
    "en": "Business Partner · PT Garam (Persero)",
    "zh": "商业合作伙伴 · PT Garam（Persero）"
  },
  "text.0021": {
    "id": "Mitra bisnis",
    "en": "PT Garam",
    "zh": "PT Garam"
  },
  "text.0022": {
    "id": "PT Garam.",
    "en": "business partner.",
    "zh": "商业合作伙伴。"
  },
  "text.0023": {
    "id": "Pasokan industri tepercaya.",
    "en": "Reliable industrial supply.",
    "zh": "可靠的工业供应。"
  },
  "text.0024": {
    "id": "DMC Pro menjadi mitra bisnis PT Garam (Persero) untuk pemasaran dan distribusi bahan baku serta produk jadi, didukung lini chemical dan industrial water treatment.",
    "en": "DMC Pro is a business partner of PT Garam (Persero) for the marketing and distribution of raw materials and finished products, supported by chemical and industrial water treatment lines.",
    "zh": "DMC Pro 是 PT Garam（Persero）的商业合作伙伴，负责原材料与成品的营销和分销，并提供化工产品及工业水处理服务。"
  },
  "text.0026": {
    "id": "Status Kemitraan",
    "en": "Partnership Status",
    "zh": "合作状态"
  },
  "text.0027": {
    "id": "Business Partner · PT Garam (Persero)",
    "en": "Business Partner · PT Garam (Persero)",
    "zh": "商业合作伙伴 · PT Garam（Persero）"
  },
  "text.0028": {
    "id": "Jelajahi Produk",
    "en": "Explore Products",
    "zh": "浏览产品"
  },
  "text.0031": {
    "id": "Video Perusahaan",
    "en": "Company Video",
    "zh": "企业视频"
  },
  "text.0032": {
    "id": "Putar video DMC Pro",
    "en": "Play the DMC Pro video",
    "zh": "播放 DMC Pro 视频"
  },
  "text.0033": {
    "id": "50",
    "en": "50",
    "zh": "50"
  },
  "text.0035": {
    "id": "Garam Industri",
    "en": "Industrial Salt",
    "zh": "工业盐"
  },
  "text.0036": {
    "id": "50%",
    "en": "50%",
    "zh": "50%"
  },
  "text.0037": {
    "id": "Bahan Baku Kimia untuk Industri",
    "en": "Industrial Chemical Raw Materials",
    "zh": "工业化工原料"
  },
  "text.0042": {
    "id": "Pasokan lokal & ekspor",
    "en": "Local & export supply",
    "zh": "国内与出口供应"
  },
  "text.0044": {
    "id": "Bahan baku & produk jadi",
    "en": "Raw & finished products",
    "zh": "原材料与成品"
  },
  "text.0046": {
    "id": "Dukungan distribusi",
    "en": "Distribution support",
    "zh": "分销支持"
  },
  "text.0048": {
    "id": "Respons teknis terarah",
    "en": "Focused technical response",
    "zh": "专业技术响应"
  },
  "text.0049": {
    "id": "Kemitraan Bisnis",
    "en": "Business Partnership",
    "zh": "商业合作"
  },
  "text.0050": {
    "id": "Business Partner",
    "en": "Business Partner",
    "zh": "Business Partner"
  },
  "text.0052": {
    "id": "PT. Dynamika Multi Compro (DMC Pro) merupakan mitra bisnis PT Garam dalam pemasaran dan distribusi garam industri, baik dalam bentuk bahan baku maupun produk jadi, untuk kebutuhan pasar lokal dan ekspor.",
    "en": "PT. Dynamika Multi Compro (DMC Pro) is a business partner of PT Garam for the marketing and distribution of industrial salt, in both raw-material and finished-product forms, serving local and export markets.",
    "zh": "PT. Dynamika Multi Compro（DMC Pro）是 PT Garam 的商业合作伙伴，负责工业盐原材料与成品的营销及分销，服务国内与出口市场。"
  },
  "text.0053": {
    "id": "Tentang DMC Pro",
    "en": "About DMC Pro",
    "zh": "关于 DMC Pro"
  },
  "text.0054": {
    "id": "Terhubung dari PT Garam hingga kebutuhan industri.",
    "en": "Connected from PT Garam to industrial demand.",
    "zh": "从 PT Garam 连接至工业需求。"
  },
  "text.0055": {
    "id": "PT. Dynamika Multi Compro adalah mitra bisnis PT Garam (Persero) yang melayani pemasaran dan distribusi bahan baku serta produk jadi, sekaligus menyediakan chemical dan solusi industrial water treatment.",
    "en": "PT. Dynamika Multi Compro is a business partner of PT Garam (Persero), serving the marketing and distribution of raw materials and finished products while also providing chemicals and industrial water treatment solutions.",
    "zh": "PT. Dynamika Multi Compro 是 PT Garam（Persero）的商业合作伙伴，提供原材料与成品的营销分销，同时供应化工产品及工业水处理解决方案。"
  },
  "text.0056": {
    "id": "Pelajari layanan kami",
    "en": "Explore our services",
    "zh": "了解我们的服务"
  },
  "text.0058": {
    "id": "Fasilitas Operasional",
    "en": "Operational Facility",
    "zh": "运营设施"
  },
  "text.0059": {
    "id": "INDONESIA",
    "en": "INDONESIA",
    "zh": "印度尼西亚"
  },
  "text.0060": {
    "id": "Komitmen Kami",
    "en": "Our Commitment",
    "zh": "我们的承诺"
  },
  "text.0061": {
    "id": "“Memberikan komitmen terbaik dalam kualitas pasokan, harga, dan koordinasi pengiriman.”",
    "en": "“Delivering our best commitment in supply quality, pricing, and shipment coordination.”",
    "zh": "“在供应质量、价格与运输协调方面始终提供最佳服务。”"
  },
  "text.0062": {
    "id": "2",
    "en": "2",
    "zh": "2"
  },
  "text.0063": {
    "id": "Bidang Usaha",
    "en": "Core Businesses",
    "zh": "核心业务"
  },
  "text.0064": {
    "id": "2",
    "en": "2",
    "zh": "2"
  },
  "text.0065": {
    "id": "Jangkauan Pasar",
    "en": "Market Reach",
    "zh": "市场覆盖"
  },
  "text.0066": {
    "id": "Lokal + Ekspor",
    "en": "Local + Export",
    "zh": "国内 + 出口"
  },
  "text.0067": {
    "id": "50 kg",
    "en": "50 kg",
    "zh": "50 kg"
  },
  "text.0068": {
    "id": "Kemasan Karung",
    "en": "Bag Packaging",
    "zh": "袋装规格"
  },
  "text.0069": {
    "id": "Bulk",
    "en": "Bulk",
    "zh": "Bulk"
  },
  "text.0070": {
    "id": "Ukuran Fleksibel",
    "en": "Flexible Sizing",
    "zh": "灵活尺寸"
  },
  "text.0071": {
    "id": "Produk & Bidang Usaha",
    "en": "Products & Business Lines",
    "zh": "产品与业务领域"
  },
  "text.0072": {
    "id": "Dua lini utama untuk kebutuhan industri.",
    "en": "Two primary lines for industrial needs.",
    "zh": "两大核心业务，满足工业需求。"
  },
  "text.0073": {
    "id": "Portofolio DMC Pro dibagi seimbang: 50% garam industri dan 50% bahan baku kimia untuk kebutuhan industri.",
    "en": "DMC Pro’s portfolio is divided equally: 50% industrial salt and 50% industrial chemical raw materials.",
    "zh": "DMC Pro 的业务组合平均分为：工业盐 50%，工业化工原料 50%。"
  },
  "text.0075": {
    "id": "Garam Industri",
    "en": "Industrial Salt",
    "zh": "工业盐"
  },
  "text.0076": {
    "id": "50% Portofolio",
    "en": "50% Portfolio",
    "zh": "50% 业务占比"
  },
  "text.0078": {
    "id": "Bahan Baku Kimia untuk Industri",
    "en": "Industrial Chemical Raw Materials",
    "zh": "工业化工原料"
  },
  "text.0079": {
    "id": "50% Portofolio",
    "en": "50% Portfolio",
    "zh": "50% 业务占比"
  },
  "text.0102": {
    "id": "Konsultasikan Kebutuhan",
    "en": "Discuss Your Requirements",
    "zh": "咨询您的需求"
  },
  "text.0104": {
    "id": "Dukungan Menyeluruh",
    "en": "End-to-end Support",
    "zh": "全流程支持"
  },
  "text.0105": {
    "id": "Dari kemitraan produk hingga dukungan operasional.",
    "en": "From product partnership to operational support.",
    "zh": "从产品合作到运营支持。"
  },
  "text.0106": {
    "id": "Kami membantu pelanggan menata kebutuhan pasokan agar lebih jelas, terukur, dan sesuai ritme bisnis.",
    "en": "We help customers structure supply requirements so they are clear, measurable, and aligned with business operations.",
    "zh": "我们帮助客户梳理供应需求，使其更清晰、可衡量，并与业务运营节奏保持一致。"
  },
  "text.0108": {
    "id": "Distribusi Produk PT Garam",
    "en": "PT Garam Product Distribution",
    "zh": "PT Garam 产品分销"
  },
  "text.0109": {
    "id": "Pemasaran dan distribusi bahan baku serta produk jadi sesuai kebutuhan pelanggan.",
    "en": "Marketing and distribution of raw materials and finished products based on customer requirements.",
    "zh": "根据客户需求营销和分销原材料与成品。"
  },
  "text.0112": {
    "id": "Pasar Lokal & Ekspor",
    "en": "Local & Export Markets",
    "zh": "国内与出口市场"
  },
  "text.0113": {
    "id": "Dukungan pasokan untuk kebutuhan domestik dan perdagangan lintas negara.",
    "en": "Supply support for domestic demand and cross-border trade.",
    "zh": "支持国内需求及跨境贸易供应。"
  },
  "text.0116": {
    "id": "Koordinasi Logistik",
    "en": "Logistics Coordination",
    "zh": "物流协调"
  },
  "text.0117": {
    "id": "Koordinasi stok, kemasan, dokumen, dan jadwal distribusi hingga tujuan.",
    "en": "Coordination of stock, packaging, documents, and delivery schedules.",
    "zh": "协调库存、包装、文件及配送计划。"
  },
  "text.0120": {
    "id": "Dukungan Teknis",
    "en": "Technical Support",
    "zh": "技术支持"
  },
  "text.0121": {
    "id": "Solusi chemical, pompa, maintenance, dan industrial water treatment.",
    "en": "Chemical solutions, pumps, maintenance, and industrial water treatment.",
    "zh": "提供化工产品、泵、维护及工业水处理解决方案。"
  },
  "text.0123": {
    "id": "Video Profil & Kemitraan",
    "en": "Company & Partnership Video",
    "zh": "企业与合作视频"
  },
  "text.0124": {
    "id": "Lihat peran DMC dalam menghubungkan produk PT Garam ke kebutuhan industri.",
    "en": "See how DMC connects PT Garam products with industrial demand.",
    "zh": "了解 DMC 如何将 PT Garam 产品连接至工业需求。"
  },
  "text.0125": {
    "id": "Modul video ini menampilkan profil perusahaan, kemitraan, kesiapan pasokan, dan jangkauan layanan DMC Pro. Video dapat diputar langsung maupun dalam layar penuh.",
    "en": "This video module presents DMC Pro’s company profile, partnership, supply readiness, and service reach. It can be played inline or in full-screen mode.",
    "zh": "该视频模块介绍 DMC Pro 的企业概况、合作关系、供应准备及服务覆盖，可直接播放或全屏观看。"
  },
  "text.0126": {
    "id": "Putar layar penuh",
    "en": "Play full screen",
    "zh": "全屏播放"
  },
  "text.0129": {
    "id": "Company Overview",
    "en": "Company Overview",
    "zh": "Company Overview"
  },
  "text.0130": {
    "id": "DMC PRO × PT GARAM (PERSERO)",
    "en": "DMC PRO × PT GARAM (PERSERO)",
    "zh": "DMC PRO × PT GARAM（PERSERO）"
  },
  "text.0132": {
    "id": "Kemitraan PT Garam",
    "en": "PT Garam Partnership",
    "zh": "PT Garam 合作关系"
  },
  "text.0133": {
    "id": "Peran DMC Pro dalam pemasaran dan distribusi bahan baku serta produk jadi.",
    "en": "DMC Pro’s role in marketing and distributing raw materials and finished products.",
    "zh": "DMC Pro 在原材料与成品营销分销中的作用。"
  },
  "text.0135": {
    "id": "Portofolio Terintegrasi",
    "en": "Integrated Portfolio",
    "zh": "综合业务组合"
  },
  "text.0136": {
    "id": "50% garam industri dan 50% bahan baku kimia untuk industri.",
    "en": "50% industrial salt and 50% industrial chemical raw materials.",
    "zh": "工业盐 50%，工业化工原料 50%。"
  },
  "text.0138": {
    "id": "Pasar Lokal & Ekspor",
    "en": "Local & Export Markets",
    "zh": "国内与出口市场"
  },
  "text.0139": {
    "id": "Koordinasi kebutuhan, stok, kemasan, dokumen, dan pengiriman.",
    "en": "Coordination of requirements, stock, packaging, documents, and shipment.",
    "zh": "协调需求、库存、包装、文件与运输。"
  },
  "text.0140": {
    "id": "Portofolio Video",
    "en": "Video Portfolio",
    "zh": "视频作品集"
  },
  "text.0141": {
    "id": "Cerita kemitraan dan kesiapan bisnis DMC dalam format video.",
    "en": "Partnership stories and DMC business readiness in video.",
    "zh": "以视频呈现 DMC 的合作关系与业务准备。"
  },
  "text.0142": {
    "id": "Galeri video menampilkan profil DMC Pro, kemitraan PT Garam, proses pasokan, fasilitas distribusi, serta solusi chemical dan industrial water treatment.",
    "en": "The gallery presents DMC Pro’s profile, PT Garam partnership, supply process, distribution facilities, and chemical and industrial water-treatment solutions.",
    "zh": "视频图库展示 DMC Pro 企业概况、PT Garam 合作关系、供应流程、分销设施，以及化工与工业水处理解决方案。"
  },
  "text.0143": {
    "id": "4 Video Portfolio · DMC Pro 2026",
    "en": "4 Video Portfolio · DMC Pro 2026",
    "zh": "4 个视频作品 · DMC Pro 2026"
  },
  "text.0146": {
    "id": "Putar Video",
    "en": "Play Video",
    "zh": "播放视频"
  },
  "text.0147": {
    "id": "Profil & Kemitraan",
    "en": "Company & Partnership",
    "zh": "企业与合作关系"
  },
  "text.0148": {
    "id": "DMC Pro × PT Garam: Kemitraan Strategis",
    "en": "DMC Pro × PT Garam: Strategic Partnership",
    "zh": "DMC Pro × PT Garam：战略合作"
  },
  "text.0149": {
    "id": "Peran DMC Pro sebagai mitra bisnis PT Garam dalam pemasaran dan distribusi bahan baku serta produk jadi.",
    "en": "DMC Pro’s role as a PT Garam business partner in marketing and distributing raw materials and finished products.",
    "zh": "DMC Pro 作为 PT Garam 商业合作伙伴，负责原材料与成品的营销和分销。"
  },
  "text.0150": {
    "id": "Company Profile",
    "en": "Company Profile",
    "zh": "企业介绍"
  },
  "text.0154": {
    "id": "Putar",
    "en": "Play",
    "zh": "播放"
  },
  "text.0155": {
    "id": "Garam Industri",
    "en": "Industrial Salt",
    "zh": "工业盐"
  },
  "text.0156": {
    "id": "Dari Sumber hingga Pasokan Industri",
    "en": "From Source to Industrial Supply",
    "zh": "从来源到工业供应"
  },
  "text.0157": {
    "id": "Gambaran proses, kualitas, dan kesiapan garam untuk kebutuhan pelanggan.",
    "en": "An overview of the process, quality, and salt readiness for customer requirements.",
    "zh": "展示盐产品的流程、质量及满足客户需求的供应准备。"
  },
  "text.0158": {
    "id": "Industrial Salt",
    "en": "Industrial Salt",
    "zh": "工业盐"
  },
  "text.0162": {
    "id": "Putar",
    "en": "Play",
    "zh": "播放"
  },
  "text.0163": {
    "id": "Fasilitas & Distribusi",
    "en": "Facilities & Distribution",
    "zh": "设施与分销"
  },
  "text.0164": {
    "id": "Kesiapan Gudang dan Distribusi",
    "en": "Warehouse and Distribution Readiness",
    "zh": "仓储与分销准备"
  },
  "text.0165": {
    "id": "Koordinasi stok, kemasan, dokumen, serta pengiriman untuk pasar lokal dan ekspor.",
    "en": "Coordination of stock, packaging, documents, and shipment for local and export markets.",
    "zh": "协调库存、包装、文件以及国内与出口市场运输。"
  },
  "text.0166": {
    "id": "Supply Readiness",
    "en": "Supply Readiness",
    "zh": "供应准备"
  },
  "text.0170": {
    "id": "Putar",
    "en": "Play",
    "zh": "播放"
  },
  "text.0171": {
    "id": "Solusi Industri",
    "en": "Industrial Solutions",
    "zh": "工业解决方案"
  },
  "text.0172": {
    "id": "Chemical & Industrial Water Treatment",
    "en": "Chemical & Industrial Water Treatment",
    "zh": "化工与工业水处理"
  },
  "text.0173": {
    "id": "Solusi untuk industri feed, food, general water treatment, pompa, dan dukungan teknis.",
    "en": "Solutions for feed, food, general water treatment, pumps, and technical support.",
    "zh": "面向饲料、食品、通用水处理、泵及技术支持的解决方案。"
  },
  "text.0174": {
    "id": "Industrial Solutions",
    "en": "Industrial Solutions",
    "zh": "工业解决方案"
  },
  "text.0176": {
    "id": "Portofolio video perusahaan, produk, fasilitas, dan layanan.",
    "en": "Company, product, facility, and service video portfolio.",
    "zh": "企业、产品、设施与服务视频作品集。"
  },
  "text.0177": {
    "id": "DMC PRO · BUSINESS PARTNER PT GARAM",
    "en": "DMC PRO · BUSINESS PARTNER OF PT GARAM",
    "zh": "DMC PRO · PT GARAM 商业合作伙伴"
  },
  "text.0178": {
    "id": "Galeri Operasional",
    "en": "Operational Gallery",
    "zh": "运营图库"
  },
  "text.0179": {
    "id": "Kesiapan lapangan yang membangun kepercayaan.",
    "en": "Field readiness that builds confidence.",
    "zh": "以现场准备建立信任。"
  },
  "text.0180": {
    "id": "Galeri menampilkan sumber produk, kondisi stok, sampel, dan fasilitas pendukung distribusi DMC Pro.",
    "en": "The gallery presents product sources, stock conditions, samples, and facilities supporting DMC Pro distribution.",
    "zh": "图库展示产品来源、库存状况、样品以及支持 DMC Pro 分销的设施。"
  },
  "text.0181": {
    "id": "Garam · Indonesia",
    "en": "Salt · Indonesia",
    "zh": "盐 · 印度尼西亚"
  },
  "text.0182": {
    "id": "Sumber Produksi",
    "en": "Production Source",
    "zh": "生产来源"
  },
  "text.0184": {
    "id": "Penyimpanan · Distribusi",
    "en": "Storage · Distribution",
    "zh": "仓储 · 分销"
  },
  "text.0185": {
    "id": "Kapasitas Gudang",
    "en": "Warehouse Capacity",
    "zh": "仓储能力"
  },
  "text.0187": {
    "id": "Inventori · 50 kg",
    "en": "Inventory · 50 kg",
    "zh": "库存 · 50公斤"
  },
  "text.0188": {
    "id": "Stok Siap",
    "en": "Ready Stock",
    "zh": "现货库存"
  },
  "text.0190": {
    "id": "Fasilitas · Penanganan",
    "en": "Facility · Handling",
    "zh": "设施 · 搬运"
  },
  "text.0191": {
    "id": "Penyimpanan Terkendali",
    "en": "Controlled Storage",
    "zh": "规范仓储"
  },
  "text.0193": {
    "id": "Sampel · Kualitas",
    "en": "Sampling · Quality",
    "zh": "取样 · 质量"
  },
  "text.0194": {
    "id": "Sampel Produk",
    "en": "Product Sample",
    "zh": "产品样品"
  },
  "text.0196": {
    "id": "Lokal · Ekspor",
    "en": "Local · Export",
    "zh": "国内 · 出口"
  },
  "text.0197": {
    "id": "Kesiapan Pasokan",
    "en": "Supply Readiness",
    "zh": "供应准备"
  },
  "text.0199": {
    "id": "Proses Kami",
    "en": "Our Process",
    "zh": "我们的流程"
  },
  "text.0200": {
    "id": "Alur sederhana, pasokan lebih terkendali.",
    "en": "A simple flow for better-controlled supply.",
    "zh": "简化流程，实现更可控的供应。"
  },
  "text.0202": {
    "id": "Kebutuhan",
    "en": "Requirements",
    "zh": "需求确认"
  },
  "text.0203": {
    "id": "Memahami jenis produk, spesifikasi, volume, dan tujuan.",
    "en": "Understanding the product, specifications, volume, and destination.",
    "zh": "了解产品类型、规格、数量及目的地。"
  },
  "text.0205": {
    "id": "Sourcing",
    "en": "Sourcing",
    "zh": "采购寻源"
  },
  "text.0206": {
    "id": "Menentukan sumber dan opsi produk yang paling sesuai.",
    "en": "Identifying the most suitable source and product options.",
    "zh": "确定最合适的来源与产品方案。"
  },
  "text.0208": {
    "id": "Kualitas & Kemasan",
    "en": "Quality & Packaging",
    "zh": "质量与包装"
  },
  "text.0209": {
    "id": "Menyiapkan kualitas, sampel, serta pilihan kemasan.",
    "en": "Preparing quality, samples, and packaging options.",
    "zh": "准备质量标准、样品与包装选项。"
  },
  "text.0211": {
    "id": "Pengiriman",
    "en": "Delivery",
    "zh": "交付"
  },
  "text.0212": {
    "id": "Mengoordinasikan dokumen dan pengiriman hingga tujuan.",
    "en": "Coordinating documents and delivery through to destination.",
    "zh": "协调文件与运输直至目的地。"
  },
  "text.0213": {
    "id": "Mulai Percakapan",
    "en": "Start a Conversation",
    "zh": "开始沟通"
  },
  "text.0214": {
    "id": "Apa kebutuhan industri Anda berikutnya?",
    "en": "What is your next industrial requirement?",
    "zh": "您的下一项工业需求是什么？"
  },
  "text.0215": {
    "id": "Ceritakan kebutuhan bahan baku, produk jadi, chemical, atau industrial water treatment Anda. Tim DMC Pro akan membantu menyiapkan opsi yang relevan.",
    "en": "Tell us your raw-material, finished-product, chemical, or industrial water-treatment requirements. The DMC Pro team will prepare relevant options.",
    "zh": "请告诉我们您对原材料、成品、化工产品或工业水处理的需求。DMC Pro 团队将为您准备合适的方案。"
  },
  "text.0216": {
    "id": "Hubungi Kami",
    "en": "Call Us",
    "zh": "致电我们"
  },
  "text.0217": {
    "id": "031 · 5057662",
    "en": "031 · 5057662",
    "zh": "031 · 5057662"
  },
  "text.0218": {
    "id": "Kunjungi",
    "en": "Visit",
    "zh": "访问网站"
  },
  "text.0219": {
    "id": "dmcpro.co.id",
    "en": "dmcpro.co.id",
    "zh": "dmcpro.co.id"
  },
  "text.0220": {
    "id": "FORM PERMINTAAN",
    "en": "REQUEST FORM",
    "zh": "需求表单"
  },
  "text.0222": {
    "id": "Nama / Perusahaan",
    "en": "Name / Company",
    "zh": "姓名 / 公司"
  },
  "text.0223": {
    "id": "Email",
    "en": "Email",
    "zh": "电子邮箱"
  },
  "text.0224": {
    "id": "Kebutuhan",
    "en": "Requirement",
    "zh": "需求类别"
  },
  "text.0225": {
    "id": "Pilih kategori",
    "en": "Select a category",
    "zh": "选择类别"
  },
  "text.0226": {
    "id": "Produk Garam: Bahan Baku & Produk Jadi",
    "en": "Salt Products: Raw & Finished",
    "zh": "盐类产品：原材料与成品"
  },
  "text.0227": {
    "id": "Chemical",
    "en": "Chemical",
    "zh": "化工产品"
  },
  "text.0228": {
    "id": "Industrial Water Treatment",
    "en": "Industrial Water Treatment",
    "zh": "工业水处理"
  },
  "text.0229": {
    "id": "Pesan",
    "en": "Message",
    "zh": "留言"
  },
  "text.0230": {
    "id": "Kirim Permintaan",
    "en": "Send Request",
    "zh": "提交需求"
  },
  "text.0233": {
    "id": "Permintaan berhasil disiapkan.",
    "en": "Your request has been prepared.",
    "zh": "您的需求已准备完成。"
  },
  "text.0234": {
    "id": "Terima kasih. Permintaan Anda sudah tersimpan dan tim DMC Pro akan segera menindaklanjuti.",
    "en": "This demo does not submit data yet. When hosted, the form can be connected to the DMC Pro team’s email or WhatsApp.",
    "zh": "此演示版本尚未发送数据。正式上线后，可将表单连接至 DMC Pro 团队的电子邮箱或 WhatsApp。"
  },
  "text.0235": {
    "id": "Kirim permintaan lain",
    "en": "Send another request",
    "zh": "提交其他需求"
  },
  "text.0238": {
    "id": "PT. Dynamika Multi Compro",
    "en": "PT. Dynamika Multi Compro",
    "zh": "PT. Dynamika Multi Compro"
  },
  "text.0239": {
    "id": "Perusahaan",
    "en": "Company",
    "zh": "公司"
  },
  "text.0240": {
    "id": "Tentang Kami",
    "en": "About Us",
    "zh": "关于我们"
  },
  "text.0241": {
    "id": "Produk",
    "en": "Products",
    "zh": "产品"
  },
  "text.0242": {
    "id": "Layanan",
    "en": "Services",
    "zh": "服务"
  },
  "text.0243": {
    "id": "Media",
    "en": "Media",
    "zh": "媒体"
  },
  "text.0244": {
    "id": "Galeri",
    "en": "Gallery",
    "zh": "图库"
  },
  "text.0245": {
    "id": "Video Perusahaan",
    "en": "Company Video",
    "zh": "企业视频"
  },
  "text.0246": {
    "id": "Kontak",
    "en": "Contact",
    "zh": "联系"
  },
  "text.0247": {
    "id": "© 2026 PT. Dynamika Multi Compro. Seluruh hak cipta dilindungi.",
    "en": "© 2026 PT. Dynamika Multi Compro. All rights reserved.",
    "zh": "© 2026 PT. Dynamika Multi Compro。保留所有权利。"
  },
  "text.0248": {
    "id": "Kembali ke atas ↑",
    "en": "Back to top ↑",
    "zh": "返回顶部 ↑"
  },
  "dynamic.business.salt.eyebrow": {
    "id": "Fokus Utama · Mitra PT Garam",
    "en": "Primary Focus · PT Garam Partner",
    "zh": "核心业务 · PT Garam 合作伙伴"
  },
  "dynamic.business.salt.title": {
    "id": "Bahan baku dan produk jadi untuk kebutuhan industri.",
    "en": "Raw materials and finished products for industrial demand.",
    "zh": "面向工业需求的原材料与成品。"
  },
  "dynamic.business.salt.description": {
    "id": "Sebagai mitra bisnis PT Garam (Persero), DMC Pro mendukung pemasaran dan distribusi garam industri dalam bentuk bahan baku maupun produk jadi untuk pasar lokal dan ekspor.",
    "en": "As a business partner of PT Garam (Persero), DMC Pro supports the marketing and distribution of industrial salt as raw materials and finished products for local and export markets.",
    "zh": "作为 PT Garam（Persero）的商业合作伙伴，DMC Pro 为国内和出口市场提供工业盐原材料与成品的营销及分销。"
  },
  "dynamic.business.salt.imageAlt": {
    "id": "Portofolio bahan baku dan produk jadi garam industri",
    "en": "Industrial salt raw-material and finished-product portfolio",
    "zh": "工业盐原材料与成品组合"
  },
  "dynamic.business.salt.bullets": {
    "id": "Bahan baku garam industri\nProduk jadi siap distribusi\nPilihan kualitas dan kemasan\nDistribusi lokal dan ekspor",
    "en": "Industrial salt raw materials\nDistribution-ready finished products\nQuality and packaging options\nLocal and export distribution",
    "zh": "工业盐原材料\n可直接分销的成品\n多种质量与包装选择\n国内及出口分销"
  },
  "dynamic.business.salt.tags": {
    "id": "Bahan Baku, Produk Jadi, Industri, Pangan, Pakan, Ekspor",
    "en": "Raw Materials, Finished Products, Industry, Food, Feed, Export",
    "zh": "原材料, 成品, 工业, 食品, 饲料, 出口"
  },
  "dynamic.business.chemical.eyebrow": {
    "id": "50% Portofolio · Bahan Baku Kimia untuk Industri",
    "en": "50% Portfolio · Industrial Chemical Raw Materials",
    "zh": "50% 业务占比 · 工业化工原料"
  },
  "dynamic.business.chemical.title": {
    "id": "Pengadaan bahan baku kimia dengan alur pasok yang terkoordinasi.",
    "en": "Chemical raw-material procurement through a coordinated supply flow.",
    "zh": "协调有序的工业化工原料采购与供应。"
  },
  "dynamic.business.chemical.description": {
    "id": "DMC Pro mendukung pengadaan dan perdagangan bahan baku kimia untuk kebutuhan industri melalui proses sourcing, koordinasi impor, penyimpanan, dan distribusi.",
    "en": "DMC Pro supports chemical raw-material procurement and trading through sourcing, import coordination, storage, and distribution.",
    "zh": "DMC Pro 通过采购、进口协调、仓储与分销，为工业客户提供化工原材料。"
  },
  "dynamic.business.chemical.imageAlt": {
    "id": "Area penyimpanan dan distribusi bahan baku kimia DMC Pro",
    "en": "DMC Pro chemical raw-material storage and distribution area",
    "zh": "DMC Pro 化工原料仓储及分销区域"
  },
  "dynamic.business.chemical.bullets": {
    "id": "Sourcing bahan baku kimia\nPengadaan lokal dan impor\nKoordinasi dokumen dan pengiriman\nPasokan sesuai kebutuhan industri",
    "en": "Chemical raw-material sourcing\nLocal and import procurement\nDocument and delivery coordination\nSupply matched to industrial demand",
    "zh": "化工原材料采购\n国内与进口采购\n文件及运输协调\n按工业需求提供供应"
  },
  "dynamic.business.chemical.tags": {
    "id": "Bahan Baku Industri, Impor, Procurement, Distribusi",
    "en": "Industrial Raw Material, Import, Procurement, Distribution",
    "zh": "工业原材料, 进口, 采购, 分销"
  }
}
JSON, true, 512, JSON_THROW_ON_ERROR);
    }
}
