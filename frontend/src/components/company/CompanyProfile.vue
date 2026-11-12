<template>
  <div class="company-profile">
    <section class="company-container company-hero" aria-labelledby="company-heading">
      <div class="company-hero-copy">
        <p class="company-eyebrow"><span></span> CV. ALPHARO ARTHA JAYA</p>
        <h1 id="company-heading">Mitra pengadaan<br>untuk <em>kemajuan</em><br>instansi Anda.</h1>
        <p class="company-intro">Solusi kontraktor dan general supplier untuk sarana prasarana serta pekerjaan konstruksi. Mendukung kebutuhan sekolah, instansi pemerintahan, dan swasta dengan mutu serta pelayanan prima.</p>
        <div class="flex flex-wrap gap-3">
          <button type="button" class="company-button company-button-primary" @click="$emit('navigate', 'request')">Ajukan Kebutuhan <ArrowUpRight class="size-4" /></button>
          <button type="button" class="company-button company-button-secondary" @click="$emit('navigate', 'tracking')"><Truck class="size-4" /> Lacak Pesanan</button>
        </div>
        <p class="company-hero-note"><ShieldCheck class="size-4 shrink-0" /> Rekanan sekolah & instansi DKI Jakarta</p>
      </div>
      <figure class="company-hero-visual">
        <img src="/images/company/logistics.jpg" width="1400" height="933" alt="Ilustrasi pengelolaan barang dan rantai pasok di gudang" fetchpriority="high">
        <figcaption>
          <span class="company-photo-label">KONTRAKTOR & GENERAL SUPPLIER</span>
          <p>Kebutuhan beragam.<br>Solusi dalam satu layanan.</p>
          <span class="company-photo-credit">Ilustrasi logistik · Unsplash</span>
        </figcaption>
      </figure>
    </section>

    <section class="company-container" aria-label="Ringkasan perusahaan">
      <dl class="company-facts grid grid-cols-2 lg:grid-cols-4">
        <div><dt>Resmi berdiri</dt><dd>06 Des 2022</dd><p>Badan usaha terdaftar</p></div>
        <div><dt>Katalog sarpras</dt><dd>2.196<span>+ SKU</span></dd><p>Terhubung stok & siap pesan</p></div>
        <div><dt>Standar dokumen</dt><dd>15<span> dokumen SPJ</span></dd><p>SPK, BAST, faktur & kelengkapan audit</p></div>
        <div><dt>Cakupan layanan</dt><dd>DKI Jakarta</dd><p>& sekitarnya, dengan armada sendiri</p></div>
      </dl>
    </section>

    <section id="company-services" class="company-container company-section" aria-labelledby="company-services-heading">
      <div class="company-section-heading">
        <div><p class="company-eyebrow">LAYANAN KAMI</p><h2 id="company-services-heading">Dari kebutuhan barang<br>hingga <em>kelengkapan dokumen.</em></h2></div>
        <p>Menghubungkan pengadaan fisik, material bermutu, dan akuntabilitas dokumen resmi dalam layanan yang terpadu.</p>
      </div>
      <div class="company-services grid grid-cols-1 md:grid-cols-3">
        <article v-for="(service, index) in services" :key="service.title">
          <div class="flex items-center justify-between"><component :is="service.icon" class="size-7 text-alpharo-600" :stroke-width="1.5" /><span class="company-service-number">0{{ index + 1 }}</span></div>
          <h3>{{ service.title }}</h3>
          <p>{{ service.description }}</p>
          <span class="company-service-detail">{{ service.detail }}</span>
        </article>
      </div>
    </section>

    <section id="company-about" class="company-about-band" aria-labelledby="company-about-heading">
      <div class="company-container company-about grid grid-cols-1 lg:grid-cols-2">
        <figure class="company-about-visual">
          <img src="/images/company/workspace.jpg" width="1200" height="801" alt="Ilustrasi sarana ruang kerja dan interior gedung" loading="lazy">
          <figcaption>Ilustrasi sarana & ruang kerja · Unsplash</figcaption>
        </figure>
        <div class="company-about-copy">
          <p class="company-eyebrow">TENTANG PERUSAHAAN</p>
          <h2 id="company-about-heading">Tumbuh bersama,<br>membangun <em>kepercayaan.</em></h2>
          <p><strong>CV. Alpharo Artha Jaya</strong> bergerak di bidang kontraktor dan general supplier. Kami menyediakan berbagai kebutuhan barang dan jasa secara cepat, dengan kualitas sebagai prioritas.</p>
          <p>Resmi berdiri pada <strong>06 Desember 2022</strong>, perusahaan kami hadir sebagai bentuk dukungan untuk memajukan pembangunan sarana dan prasarana di Indonesia.</p>
          <p>Kami memperhatikan setiap barang dan jasa yang diberikan untuk menjaga kepercayaan perusahaan kecil, menengah, dan besar, baik sektor swasta maupun instansi pemerintahan.</p>
          <p>Pengalaman dan kompetensi di bidangnya, didukung sumber daya manusia yang profesional, menjadi landasan kami dalam memberikan pelayanan terbaik dan menjamin kepuasan klien.</p>
          <a href="https://maps.app.goo.gl/3qdNy8ETp7YdoGuU9" target="_blank" rel="noopener noreferrer" class="company-text-link">Kenali lokasi kami <ArrowUpRight class="size-4" /></a>
        </div>
      </div>
    </section>

    <section class="company-container company-section company-purpose grid grid-cols-1 lg:grid-cols-2" aria-label="Visi dan misi perusahaan">
      <div>
        <p class="company-eyebrow">VISI PERUSAHAAN</p>
        <h2>Komitmen untuk<br>terus <em>bertumbuh.</em></h2>
        <blockquote>“Menjadi perusahaan kontraktor dan general supplier yang terpercaya dengan komitmen untuk selalu tumbuh berkembang secara konsisten serta memberikan pelayanan yang prima.”</blockquote>
        <p class="company-purpose-note"><ShieldCheck class="size-4 shrink-0" /> Terpercaya & konsisten dalam mutu dan layanan</p>
      </div>
      <div>
        <p class="company-eyebrow">MISI PERUSAHAAN</p>
        <ol class="company-missions">
          <li v-for="(mission, index) in missions" :key="mission.title"><span>0{{ index + 1 }}</span><div><h3>{{ mission.title }}</h3><p>{{ mission.description }}</p></div></li>
        </ol>
      </div>
    </section>

    <section class="company-location-band" aria-labelledby="company-location-heading">
      <div class="company-container company-location grid grid-cols-1 lg:grid-cols-2">
        <div>
          <p class="company-eyebrow">TEMUI KAMI</p>
          <h2 id="company-location-heading">Kantor & workshop.<br><em>Siap melayani Anda.</em></h2>
          <dl class="company-contact">
            <div><MapPin class="size-5" /><div><dt>Alamat kantor</dt><dd>Ciracas / Cipayung, Jakarta Timur</dd><p>Daerah Khusus Ibukota Jakarta</p></div></div>
            <div><Clock class="size-5" /><div><dt>Jam operasional</dt><dd>Senin – Jumat, 08.00 – 17.00 WIB</dd><p>Pengajuan melalui portal tersedia 24 jam</p></div></div>
            <div><CreditCard class="size-5" /><div><dt>Rekening resmi perusahaan</dt><dd class="tabular-nums">BCA · 52323628813</dd><p>Bank BCA Jakarta a.n. CV ALPHARO ARTHA JAYA</p></div></div>
          </dl>
          <a href="https://maps.app.goo.gl/3qdNy8ETp7YdoGuU9" target="_blank" rel="noopener noreferrer" class="company-text-link">Buka di Google Maps <ArrowUpRight class="size-4" /></a>
        </div>
        <div class="company-map">
          <iframe title="Lokasi kantor CV Alpharo Artha Jaya di Google Maps" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15861.32964469573!2d106.88105992855836!3d-6.350991720332151!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69ed42473b8859%3A0xffcc35b473557290!2sCV.%20ALPHARO%20ARTHA%20JAYA!5e0!3m2!1sid!2sid!4v1794116955907!5m2!1sid!2sid" width="600" height="450" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
          <div class="company-map-footer"><span>Lokasi kantor operasional</span><a href="https://maps.app.goo.gl/3qdNy8ETp7YdoGuU9" target="_blank" rel="noopener noreferrer">Buka Google Maps <ArrowUpRight class="size-3" /></a></div>
        </div>
      </div>
    </section>

    <section class="company-container company-section" aria-labelledby="company-cta-heading">
      <div class="company-cta">
        <div><p class="company-eyebrow">MULAI KERJA SAMA</p><h2 id="company-cta-heading">Kebutuhan instansi Anda,<br>langkah berikutnya bersama kami.</h2><p>Ajukan kebutuhan melalui katalog terpadu 2.196 SKU untuk mendukung kegiatan belajar mengajar dan operasional instansi Anda.</p></div>
        <div class="flex flex-col items-start gap-4"><button type="button" class="company-button company-button-white" @click="$emit('navigate', 'request')">Buat Pesanan Sekarang <ArrowUpRight class="size-4" /></button><button type="button" class="company-cta-link" @click="$emit('navigate', 'tracking')">Lacak Pesanan / Resi BAST <ArrowRight class="size-4" /></button></div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ArrowRight, ArrowUpRight, Boxes, BriefcaseBusiness, Clock, CreditCard, FileCheck2, MapPin, ShieldCheck, Truck } from 'lucide-vue-next';

defineEmits(['navigate']);

const services = [
  { icon: Boxes, title: 'General Supplier & Sarpras', description: 'Penyediaan mebeler sekolah dan kantor, perangkat komputer & IT, laboratorium, sound system, ATK, serta sarana penunjang dari katalog 2.196 SKU.', detail: 'Kebutuhan sekolah & operasional instansi' },
  { icon: BriefcaseBusiness, title: 'Kontraktor & Konstruksi', description: 'Renovasi ruang kelas, perbaikan sarana fisik, pemeliharaan berkala infrastruktur gedung, pengecatan, hingga penataan interior instansi.', detail: 'Pekerjaan fisik & pemeliharaan gedung' },
  { icon: FileCheck2, title: 'Legalitas & SPJ Otomatis', description: 'Kelengkapan 15 dokumen resmi: Surat Penawaran, SPK, Surat Jalan, BAST rekanan & sekolah, Faktur Pajak, Kuitansi, dan SPJ siap audit.', detail: 'Dokumen pengadaan yang tertib & lengkap' },
];
const missions = [
  { title: 'Kemitraan yang profesional', description: 'Membangun dan menjaga kerja sama yang profesional, unggul, dan kompeten bersama seluruh mitra.' },
  { title: 'Pengembangan berkelanjutan', description: 'Meningkatkan optimalitas kerja dan nilai pelayanan secara terus-menerus agar mampu bersaing dalam industri jasa konstruksi dan pengadaan.' },
  { title: 'Kualitas sesuai kebutuhan', description: 'Mengutamakan pencapaian kualitas kerja yang sesuai harapan dan kebutuhan mitra.' },
];
</script>

<style scoped>
.company-profile { --company-ink: #142740; --company-muted: #5e6c7d; background: #fff; color: var(--company-ink); }
.company-container { width: min(1200px, 100% - 64px); margin-inline: auto; }
.company-section { padding-block: 88px; }
.company-eyebrow { display: flex; align-items: center; gap: 9px; color: #2563eb; font-size: 11px; line-height: 1.5; letter-spacing: .13em; font-weight: 700; }
.company-eyebrow > span { width: 6px; height: 6px; border-radius: 50%; background: #2563eb; }
h1, h2, h3 { color: var(--company-ink); }
h1 { font-size: clamp(42px, 4.25vw, 62px); line-height: 1.12; letter-spacing: -.05em; font-weight: 650; }
h2 { font-size: clamp(29px, 3vw, 40px); line-height: 1.2; letter-spacing: -.04em; font-weight: 600; }
em { color: #2563eb; font-family: Georgia, 'Times New Roman', serif; font-weight: 400; }
.company-hero { display: grid; grid-template-columns: 1.05fr 1fr; align-items: center; gap: 64px; padding-block: 68px 52px; }
.company-hero-copy { display: flex; flex-direction: column; align-items: flex-start; gap: 25px; min-width: 0; }
.company-intro { max-width: 480px; font-size: 15px; line-height: 1.9; color: var(--company-muted); }
.company-button { display: inline-flex; justify-content: center; align-items: center; gap: 12px; min-height: 46px; padding: 12px 20px; border: 1px solid transparent; border-radius: 7px; font-size: 13px; font-weight: 600; line-height: 1.5; cursor: pointer; }
.company-button-primary { color: #fff; background: #2563eb; }
.company-button-primary:hover { background: #1d4ed8; }
.company-button-secondary { color: #273e5d; border-color: #dce3ed; background: #fff; }
.company-button-secondary:hover { background: #f1f6ff; border-color: #b4cbef; }
.company-hero-note { display: flex; gap: 8px; align-items: center; color: var(--company-muted); font-size: 11px; }
.company-hero-note svg { color: #2563eb; }
.company-hero-visual { position: relative; min-width: 0; height: 490px; border-radius: 16px; overflow: hidden; background: #243b54; }
.company-hero-visual > img { width: 100%; height: 100%; object-fit: cover; object-position: 54% center; }
.company-hero-visual figcaption { position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: flex-end; padding: 32px; color: #fff; background: linear-gradient(180deg, transparent 35%, #091b32e8 100%); }
.company-photo-label { font-size: 9px; font-weight: 600; letter-spacing: .12em; color: #dbeafe; }
.company-hero-visual figcaption p { font-size: 25px; font-weight: 500; line-height: 1.35; letter-spacing: -.025em; margin-top: 10px; }
.company-photo-credit { margin-top: 15px; font-size: 9px; color: #d7e1ee; }
.company-facts { border-block: 1px solid #e3e9f1; padding-block: 28px; }
.company-facts > div { padding-inline: 28px; border-right: 1px solid #e3e9f1; }
.company-facts > div:first-child { padding-left: 0; }
.company-facts > div:last-child { border: 0; padding-right: 0; }
.company-facts dt { font-size: 11px; color: var(--company-muted); }
.company-facts dd { font-size: 25px; font-weight: 600; letter-spacing: -.04em; margin-block: 6px; }
.company-facts dd span { font-size: 13px; letter-spacing: -.01em; }
.company-facts p { font-size: 10px; color: var(--company-muted); line-height: 1.6; }
.company-section-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 40px; }
.company-section-heading h2 { margin-top: 14px; }
.company-section-heading > p { max-width: 340px; font-size: 14px; line-height: 1.85; color: var(--company-muted); }
.company-services { margin-top: 42px; }
.company-services article { display: flex; flex-direction: column; padding: 28px 32px 0; border-top: 1px solid #e3e9f1; border-right: 1px solid #e3e9f1; min-width: 0; }
.company-services article:first-child { padding-left: 0; }
.company-services article:last-child { border-right: 0; padding-right: 0; }
.company-service-number { font-size: 12px; color: #8a9aae; font-variant-numeric: tabular-nums; }
.company-services h3 { font-size: 18px; font-weight: 600; margin-block: 24px 12px; letter-spacing: -.025em; }
.company-services article > p { font-size: 13px; line-height: 1.9; color: var(--company-muted); flex: 1; }
.company-service-detail { margin-top: 24px; font-size: 10px; font-weight: 500; color: #2563eb; }
.company-about-band { background: #f5f8fc; }
.company-about { gap: 64px; padding-block: 72px; align-items: stretch; }
.company-about-visual { min-height: 500px; position: relative; overflow: hidden; border-radius: 16px; }
.company-about-visual img { position: absolute; width: 100%; height: 100%; object-fit: cover; object-position: 35% center; }
.company-about-visual figcaption { position: absolute; bottom: 0; inset-inline: 0; padding: 32px 20px 16px; font-size: 9px; color: #fff; background: linear-gradient(transparent, #142740b3); }
.company-about-copy { display: flex; flex-direction: column; align-items: flex-start; gap: 15px; padding-block: 10px; }
.company-about-copy h2 { margin-bottom: 7px; }
.company-about-copy > p:not(.company-eyebrow) { color: var(--company-muted); font-size: 13px; line-height: 1.85; }
.company-about-copy strong { font-weight: 600; color: #34465e; }
.company-text-link { display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: #1d4ed8; padding-block: 8px; }
.company-text-link:hover { text-decoration: underline; text-underline-offset: 4px; }
.company-purpose { gap: 88px; }
.company-purpose h2 { margin-block: 15px 22px; }
.company-purpose blockquote { padding-left: 22px; border-left: 2px solid #bad2fc; font-size: 16px; line-height: 1.9; color: #40536b; }
.company-purpose-note { display: flex; align-items: center; gap: 8px; color: #2563eb; font-size: 10px; margin-top: 25px; }
.company-missions { margin-top: 23px; }
.company-missions li { display: flex; gap: 20px; padding-block: 22px; border-bottom: 1px solid #e3e9f1; }
.company-missions li:first-child { padding-top: 0; }
.company-missions li:last-child { border-bottom: 0; padding-bottom: 0; }
.company-missions li > span { font-size: 11px; color: #2563eb; padding-top: 3px; font-weight: 600; }
.company-missions h3 { font-size: 15px; font-weight: 600; margin-bottom: 7px; }
.company-missions p { font-size: 13px; line-height: 1.85; color: var(--company-muted); }
.company-location-band { background: #f5f8fc; }
.company-location { gap: 64px; padding-block: 64px; align-items: stretch; }
.company-location h2 { margin-block: 14px 25px; }
.company-contact { display: flex; flex-direction: column; gap: 22px; }
.company-contact > div { display: flex; align-items: flex-start; gap: 14px; }
.company-contact svg { color: #2563eb; margin-top: 3px; flex-shrink: 0; stroke-width: 1.5; }
.company-contact dt { color: var(--company-muted); font-size: 10px; margin-bottom: 4px; }
.company-contact dd { font-size: 13px; font-weight: 500; }
.company-contact p { font-size: 11px; color: var(--company-muted); margin-top: 4px; }
.company-location .company-text-link { margin-top: 23px; }
.company-map { display: flex; flex-direction: column; min-height: 440px; border-radius: 16px; overflow: hidden; background: #eaf1fa; border: 1px solid #dbe4ef; }
.company-map iframe { width: 100%; min-height: 360px; flex: 1; border: 0; }
.company-map-footer { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; padding: 14px 20px; background: #fff; border-top: 1px solid #dbe4ef; font-size: 10px; color: var(--company-muted); }
.company-map-footer a { display: inline-flex; align-items: center; gap: 6px; color: #1d4ed8; font-weight: 600; }
.company-map-footer a:hover { text-decoration: underline; }
.company-cta { display: flex; align-items: center; justify-content: space-between; gap: 44px; background: #142d50; border-radius: 16px; padding: 48px; }
.company-cta .company-eyebrow { color: #93baff; }
.company-cta h2 { font-size: clamp(26px, 2.8vw, 34px); color: #fff; margin-block: 13px 17px; }
.company-cta > div:first-child > p:last-child { color: #c0cee0; font-size: 12px; line-height: 1.9; max-width: 570px; }
.company-cta > div:last-child { flex-shrink: 0; }
.company-button-white { background: #fff; color: #142d50; }
.company-button-white:hover { background: #e7efff; }
.company-cta-link { display: inline-flex; align-items: center; gap: 10px; color: #dbe8fc; font-size: 11px; cursor: pointer; }
.company-cta-link:hover { color: #fff; text-decoration: underline; }
@media (min-width: 768px) and (max-width: 1100px) {
  .company-hero { gap: 32px; }
  .company-hero-visual { height: 460px; }
  .company-facts > div { padding-inline: 20px; }
  .company-facts dd { font-size: 22px; }
  .company-services article { padding-inline: 22px; }
}
@media (max-width: 1023px) {
  .company-about, .company-location { gap: 36px; }
  .company-about-visual { min-height: 350px; }
  .company-purpose { gap: 40px; }
  .company-facts { gap: 28px 0; }
  .company-facts > div:nth-child(2) { border: 0; }
  .company-facts > div:nth-child(3) { padding-left: 0; }
  .company-cta { padding: 36px; flex-direction: column; align-items: flex-start; gap: 25px; }
}
@media (max-width: 767px) {
  .company-container { width: calc(100% - 40px); }
  .company-section { padding-block: 52px; }
  .company-hero { grid-template-columns: 1fr; gap: 32px; padding-block: 40px 32px; }
  .company-hero-copy { gap: 21px; }
  h1 { font-size: clamp(39px, 8.3vw, 57px); }
  .company-intro { font-size: 14px; max-width: 520px; }
  .company-button { padding-inline: 15px; font-size: 12px; }
  .company-hero-visual { height: 350px; border-radius: 12px; }
  .company-hero-visual figcaption { padding: 24px; }
  .company-facts { padding-block: 24px; }
  .company-facts > div { padding-inline: 18px; }
  .company-facts dd { font-size: 22px; }
  .company-facts dd span { font-size: 11px; }
  .company-section-heading { align-items: flex-start; flex-direction: column; gap: 20px; }
  .company-section-heading > p { max-width: 520px; font-size: 13px; }
  .company-services { margin-top: 28px; gap: 28px; }
  .company-services article, .company-services article:first-child, .company-services article:last-child { padding: 24px 0 0; border-right: 0; }
  .company-services h3 { margin-top: 18px; }
  .company-service-detail { margin-top: 16px; }
  .company-about, .company-location { padding-block: 48px; }
  .company-about-visual { min-height: 320px; }
  .company-map { min-height: 340px; }
  .company-cta { padding: 28px 24px; border-radius: 12px; }
}
@media (max-width: 359px) {
  .company-container { width: calc(100% - 32px); }
  h1 { font-size: 34px; }
  .company-facts dd { font-size: 19px; }
  .company-facts > div { padding-inline: 12px; }
}
</style>
