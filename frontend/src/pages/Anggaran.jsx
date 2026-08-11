import React, { useState, useMemo, useEffect } from 'react';
import Navbar from '../components/Navbar';
import { Icons, Logo, BusinessAvatar } from '../components/Icons';

// Currency and numbers formatter
const formatRupiah = (value) => {
  return "Rp " + Math.abs(value).toLocaleString('id-ID', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

const formatNumber = (value) => {
  return value.toLocaleString('id-ID', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

// Generates 21 sections of data, calibrated so that the grand total matches exactly Rp 995.971.307,58
const getInitialData = () => {
  const sectionA = {
    code: 'A',
    name: 'PEKERJAAN PERSIAPAN',
    items: [
      { name: 'Pembersihan lapangan dan perataan', volume: 139.52, unit: 'm2', unitPrice: 13965 },
      { name: 'Pembuatan pagar sementara dari seng gelombang tinggi 2 meter', volume: 48.00, unit: 'm2', unitPrice: 212496.38 },
      { name: 'Pengukuran dan pemasangan Bouwplank', volume: 117.80, unit: 'm1', unitPrice: 77364 },
      { name: 'Penggalian tanah biasa sedalam 2 m', volume: 99.90, unit: 'm3', unitPrice: 80608.50 },
      { name: 'Pengurugan kembali galian tanah', volume: 27.66, unit: 'm3', unitPrice: 47565 },
      { name: 'Pembuatan gudang semen dan peralatan', volume: 9.00, unit: 'm2', unitPrice: 567606.38 },
      { name: 'Mengangkut tanah sisa galian', volume: 72.24, unit: 'm3', unitPrice: 17913 },
      { name: 'Pemadatan tanah tanah (per 20 cm)', volume: 32.40, unit: 'm3', unitPrice: 47565 },
      { name: 'Uji sondir dan hand boring', volume: 1.00, unit: 'paket', unitPrice: 3150000 }
    ]
  };

  const rawSectionsBToU = [
    {
      code: 'B',
      name: 'PEKERJAAN TANAH DAN PONDASI',
      items: [
        { name: 'Galian tanah pondasi menerus', volume: 120.00, unit: 'm3', unitPrice: 75000 },
        { name: 'Urugan pasir di bawah pondasi t=10cm', volume: 15.50, unit: 'm3', unitPrice: 180000 },
        { name: 'Pondasi batu belah 1:4', volume: 85.00, unit: 'm3', unitPrice: 850000 },
        { name: 'Pondasi bored pile dia. 30cm', volume: 160.00, unit: 'm1', unitPrice: 280000 },
        { name: 'Urugan tanah kembali bekas galian', volume: 40.00, unit: 'm3', unitPrice: 35000 },
        { name: 'Cerucuk bambu dia. 8-10cm L=3m', volume: 150.00, unit: 'batang', unitPrice: 25000 },
        { name: 'Lantai kerja beton tumbuk t=5cm', volume: 8.50, unit: 'm3', unitPrice: 950000 },
        { name: 'Urugan sirtu di bawah lantai padat', volume: 35.00, unit: 'm3', unitPrice: 220000 },
        { name: 'Pemadatan tanah area pondasi', volume: 120.00, unit: 'm2', unitPrice: 18000 }
      ]
    },
    {
      code: 'C',
      name: 'PEKERJAAN STRUKTUR BETON LANTAI 1',
      items: [
        { name: 'Sloof beton bertulang 15/20 K-250', volume: 12.50, unit: 'm3', unitPrice: 4200000 },
        { name: 'Kolom beton bertulang 30/30 K-300', volume: 8.40, unit: 'm3', unitPrice: 4500000 },
        { name: 'Balok beton bertulang 20/40 K-300', volume: 10.20, unit: 'm3', unitPrice: 4500000 },
        { name: 'Plat lantai beton bertulang t=12cm K-300', volume: 18.50, unit: 'm3', unitPrice: 4800000 },
        { name: 'Kolom praktis 15/15 K-175', volume: 4.80, unit: 'm3', unitPrice: 3500000 },
        { name: 'Ring balk 15/20 K-175', volume: 5.20, unit: 'm3', unitPrice: 3800000 },
        { name: 'Tangga beton bertulang K-250', volume: 3.50, unit: 'm3', unitPrice: 4300000 },
        { name: 'Bekisting kayu kruing untuk sloof', volume: 45.00, unit: 'm2', unitPrice: 180000 },
        { name: 'Kawat beton / bendrat', volume: 85.00, unit: 'kg', unitPrice: 22000 }
      ]
    },
    {
      code: 'D',
      name: 'PEKERJAAN STRUKTUR BETON LANTAI 2',
      items: [
        { name: 'Kolom beton bertulang 30/30 K-300', volume: 7.20, unit: 'm3', unitPrice: 4600000 },
        { name: 'Balok beton bertulang 20/40 K-300', volume: 9.80, unit: 'm3', unitPrice: 4600000 },
        { name: 'Plat lantai beton bertulang t=12cm K-300', volume: 16.50, unit: 'm3', unitPrice: 4900000 },
        { name: 'Kolom praktis 15/15 K-175', volume: 4.20, unit: 'm3', unitPrice: 3600000 },
        { name: 'Ring balk 15/20 K-175', volume: 4.80, unit: 'm3', unitPrice: 3900000 },
        { name: 'Bekisting plywood 9mm untuk balok', volume: 120.00, unit: 'm2', unitPrice: 165000 },
        { name: 'Bekisting plywood 9mm untuk plat', volume: 150.00, unit: 'm2', unitPrice: 175000 },
        { name: 'Besi beton ulir D13', volume: 850.00, unit: 'kg', unitPrice: 16500 },
        { name: 'Besi beton polos d8', volume: 420.00, unit: 'kg', unitPrice: 15500 }
      ]
    },
    {
      code: 'E',
      name: 'PEKERJAAN STRUKTUR BETON LANTAI 3',
      items: [
        { name: 'Kolom beton bertulang 25/25 K-300', volume: 5.40, unit: 'm3', unitPrice: 4700000 },
        { name: 'Balok beton bertulang 20/35 K-300', volume: 8.20, unit: 'm3', unitPrice: 4700000 },
        { name: 'Plat atap beton bertulang t=10cm K-300', volume: 12.00, unit: 'm3', unitPrice: 5000000 },
        { name: 'Kolom praktis 15/15 K-175', volume: 3.60, unit: 'm3', unitPrice: 3700000 },
        { name: 'Ring balk 15/20 K-175', volume: 4.20, unit: 'm3', unitPrice: 4000000 },
        { name: 'Bekisting plywood 9mm untuk kolom', volume: 85.00, unit: 'm2', unitPrice: 185000 },
        { name: 'Besi beton ulir D13', volume: 680.00, unit: 'kg', unitPrice: 16500 },
        { name: 'Besi beton polos d8', volume: 310.00, unit: 'kg', unitPrice: 15500 },
        { name: 'Pekerjaan curing beton plat atap', volume: 1.00, unit: 'ls', unitPrice: 1200000 }
      ]
    },
    {
      code: 'F',
      name: 'PEKERJAAN DINDING DAN PLESTERAN',
      items: [
        { name: 'Pasang dinding bata merah tebal 1/2 bata 1:4', volume: 480.00, unit: 'm2', unitPrice: 115000 },
        { name: 'Pasang dinding bata merah tebal 1/2 bata 1:2', volume: 95.00, unit: 'm2', unitPrice: 125000 },
        { name: 'Plesteran dinding tebal 15mm 1:4', volume: 960.00, unit: 'm2', unitPrice: 65000 },
        { name: 'Plesteran dinding tebal 15mm 1:2', volume: 190.00, unit: 'm2', unitPrice: 72000 },
        { name: 'Acian dinding plesteran interior', volume: 960.00, unit: 'm2', unitPrice: 38000 },
        { name: 'Acian dinding plesteran eksterior', volume: 190.00, unit: 'm2', unitPrice: 42000 },
        { name: 'Pasangan roster semen 20x20', volume: 85.00, unit: 'unit', unitPrice: 35000 },
        { name: 'Pasangan glass block 20x20', volume: 40.00, unit: 'unit', unitPrice: 65000 },
        { name: 'Pekerjaan tali air plesteran', volume: 180.00, unit: 'm1', unitPrice: 15000 }
      ]
    },
    {
      code: 'G',
      name: 'PEKERJAAN KUSEN, PINTU, DAN JENDELA',
      items: [
        { name: 'Kusen aluminium 4" warna hitam anodized', volume: 145.00, unit: 'm1', unitPrice: 125000 },
        { name: 'Daun pintu panel kayu kamper oven', volume: 8.00, unit: 'unit', unitPrice: 2800000 },
        { name: 'Daun pintu kaca frame aluminium', volume: 6.00, unit: 'unit', unitPrice: 1950000 },
        { name: 'Daun jendela kaca swing frame aluminium', volume: 18.00, unit: 'unit', unitPrice: 950000 },
        { name: 'Kaca polos tebal 5mm untuk jendela', volume: 32.50, unit: 'm2', unitPrice: 185000 },
        { name: 'Kunci pintu utama type Mortise Lock Dekkson', volume: 2.00, unit: 'set', unitPrice: 850000 },
        { name: 'Kunci pintu kamar type Lever Handle Dekkson', volume: 12.00, unit: 'set', unitPrice: 450000 },
        { name: 'Engsel pintu stainless steel 4"', volume: 42.00, unit: 'pcs', unitPrice: 4500 },
        { name: 'Door closer type hold open Dekkson', volume: 4.00, unit: 'pcs', unitPrice: 320000 }
      ]
    },
    {
      code: 'H',
      name: 'PEKERJAAN RANGKA ATAP DAN GENTENG',
      items: [
        { name: 'Rangka atap baja ringan spandek t=0.75mm', volume: 165.00, unit: 'm2', unitPrice: 185000 },
        { name: 'Penutup atap genteng keramik Kanmuri', volume: 165.00, unit: 'm2', unitPrice: 175000 },
        { name: 'Pasang nok genteng keramik Kanmuri', volume: 24.50, unit: 'm1', unitPrice: 95000 },
        { name: 'Pasang flashing seng talang BJLS 30', volume: 18.00, unit: 'm1', unitPrice: 65000 },
        { name: 'Lisplank GRC t=9mm lebar 30cm', volume: 36.00, unit: 'm1', unitPrice: 85000 },
        { name: 'Pekerjaan gording baja profil C 125', volume: 640.00, unit: 'kg', unitPrice: 22000 },
        { name: 'Pekerjaan ikatan angin dia. 12mm', volume: 85.00, unit: 'kg', unitPrice: 24000 },
        { name: 'Cat meni besi untuk rangka baja', volume: 120.00, unit: 'm2', unitPrice: 35000 },
        { name: 'Aluminium foil single side penahan panas', volume: 165.00, unit: 'm2', unitPrice: 22000 }
      ]
    },
    {
      code: 'I',
      name: 'PEKERJAAN PLAFON GYPSUM',
      items: [
        { name: 'Rangka plafon hollow galvanis 40x40 & 20x40', volume: 380.00, unit: 'm2', unitPrice: 85000 },
        { name: 'Plafon gypsum board tebal 9mm Jaya Board', volume: 320.00, unit: 'm2', unitPrice: 68000 },
        { name: 'Plafon GRC board tebal 4mm (toilet & teras)', volume: 60.00, unit: 'm2', unitPrice: 72000 },
        { name: 'List profil gypsum lebar 10cm', volume: 420.00, unit: 'm1', unitPrice: 28000 },
        { name: 'Compound gypsum & tape sambungan', volume: 380.00, unit: 'm2', unitPrice: 12000 },
        { name: 'Pekerjaan drop ceiling gypsum', volume: 65.00, unit: 'm1', unitPrice: 95000 },
        { name: 'Manhole plafon ukuran 60x60 frame aluminium', volume: 6.00, unit: 'unit', unitPrice: 150000 },
        { name: 'Kawat penggantung plafon dia. 4mm', volume: 450.00, unit: 'pcs', unitPrice: 4500 },
        { name: 'Pekerjaan shadowline aluminium keliling', volume: 120.00, unit: 'm1', unitPrice: 18000 }
      ]
    },
    {
      code: 'J',
      name: 'PEKERJAAN LANTAI DAN KERAMIK',
      items: [
        { name: 'Lantai Homogeneous Tile 60x60 polished Roman', volume: 280.00, unit: 'm2', unitPrice: 195000 },
        { name: 'Lantai Homogeneous Tile 60x60 unpolished Roman', volume: 45.00, unit: 'm2', unitPrice: 210000 },
        { name: 'Keramik lantai kamar mandi 30x30 Roman anti slip', volume: 24.00, unit: 'm2', unitPrice: 135000 },
        { name: 'Keramik dinding kamar mandi 30x60 Roman', volume: 72.00, unit: 'm2', unitPrice: 165000 },
        { name: 'Keramik dinding dapur 30x60 Roman', volume: 18.50, unit: 'm2', unitPrice: 175000 },
        { name: 'Plint lantai keramik ukuran 10x60', volume: 340.00, unit: 'm1', unitPrice: 28000 },
        { name: 'Screeding lantai dasar tebal 3cm 1:4', volume: 325.00, unit: 'm2', unitPrice: 48000 },
        { name: 'Perekat keramik MU-400 Granit Fix', volume: 85.00, unit: 'sak', unitPrice: 145000 },
        { name: 'Pengisi nat keramik / grout Roman', volume: 65.00, unit: 'kg', unitPrice: 18000 }
      ]
    },
    {
      code: 'K',
      name: 'PEKERJAAN INSTALASI AIR BERSIH & KOTOR',
      items: [
        { name: 'Instalasi pipa air bersih PPR PN-10 dia. 1/2" Wavin', volume: 85.00, unit: 'm1', unitPrice: 38000 },
        { name: 'Instalasi pipa air bersih PPR PN-10 dia. 3/4" Wavin', volume: 42.00, unit: 'm1', unitPrice: 48000 },
        { name: 'Instalasi pipa air kotor PVC kelas D dia. 3" Wavin', volume: 65.00, unit: 'm1', unitPrice: 75000 },
        { name: 'Instalasi pipa air bekas PVC kelas D dia. 4" Wavin', volume: 85.00, unit: 'm1', unitPrice: 95000 },
        { name: 'Pipa vent PVC kelas D dia. 1-1/2" Wavin', volume: 32.00, unit: 'm1', unitPrice: 28000 },
        { name: 'Galian tanah untuk saluran pipa air kotor', volume: 45.00, unit: 'm3', unitPrice: 75000 },
        { name: 'Urugan pasir kempa pipa t=10cm', volume: 8.50, unit: 'm3', unitPrice: 180000 },
        { name: 'Pekerjaan bak kontrol bata 40x40 t=50cm', volume: 6.00, unit: 'unit', unitPrice: 450000 },
        { name: 'Septic tank biofil kapasitas 1500 liter', volume: 1.00, unit: 'unit', unitPrice: 4200000 }
      ]
    },
    {
      code: 'L',
      name: 'PEKERJAAN SANITAIR & FAUCETS',
      items: [
        { name: 'Monoblock closet duduk type CW421J Toto putih', volume: 6.00, unit: 'unit', unitPrice: 2650000 },
        { name: 'Wastafel gantung type LW230J Toto putih', volume: 6.00, unit: 'unit', unitPrice: 850000 },
        { name: 'Kran dinding leher angsa dapur type TX603KES Toto', volume: 2.00, unit: 'unit', unitPrice: 950000 },
        { name: 'Kran shower mixer tiang panas dingin Toto', volume: 6.00, unit: 'unit', unitPrice: 3200000 },
        { name: 'Jet shower toilet type TX403SB Toto putih', volume: 6.00, unit: 'unit', unitPrice: 380000 },
        { name: 'Floor drain stainless steel type TX1BN Toto', volume: 8.00, unit: 'pcs', unitPrice: 280000 },
        { name: 'Gantungan handuk stainless steel Toto', volume: 6.00, unit: 'pcs', unitPrice: 320000 },
        { name: 'Tempat sabun keramik tanam Toto', volume: 6.00, unit: 'pcs', unitPrice: 145000 },
        { name: 'Cermin wastafel beveled tebal 5mm 60x80', volume: 6.00, unit: 'pcs', unitPrice: 350000 }
      ]
    },
    {
      code: 'M',
      name: 'PEKERJAAN INSTALASI LISTRIK',
      items: [
        { name: 'Instalasi titik lampu kabel NYM 3x2.5mm Eternal', volume: 68.00, unit: 'titik', unitPrice: 245000 },
        { name: 'Instalasi titik stop kontak kabel NYM 3x2.5mm Eternal', volume: 42.00, unit: 'titik', unitPrice: 265000 },
        { name: 'Instalasi titik AC kabel NYM 3x2.5mm + pipa drain', volume: 8.00, unit: 'titik', unitPrice: 480000 },
        { name: 'Instalasi titik exhaust fan toilet + pipa exhaust', volume: 6.00, unit: 'titik', unitPrice: 320000 },
        { name: 'Instalasi pipa conduit PVC high impact dia. 20mm', volume: 180.00, unit: 'batang', unitPrice: 12500 },
        { name: 'T-Dos & Cross-Dos PVC Clipsal', volume: 120.00, unit: 'pcs', unitPrice: 4500 },
        { name: 'Kawat penarik kabel / fishing tape', volume: 1.00, unit: 'roll', unitPrice: 280000 },
        { name: 'Instalasi arde ground tembaga BC-16 L=6m', volume: 1.00, unit: 'lot', unitPrice: 1450000 },
        { name: 'Pengujian instalasi & megger test tahanan isolasi', volume: 1.00, unit: 'ls', unitPrice: 1200000 }
      ]
    },
    {
      code: 'N',
      name: 'PEKERJAAN ARMATUR & LAMPU',
      items: [
        { name: 'Lampu LED Downlight 9W Panasonic round putih', volume: 54.00, unit: 'pcs', unitPrice: 95000 },
        { name: 'Lampu LED Strip warm white untuk drop ceiling 12V', volume: 120.00, unit: 'm1', unitPrice: 45000 },
        { name: 'Saklar tunggal type Wide Series Panasonic', volume: 8.00, unit: 'pcs', unitPrice: 28000 },
        { name: 'Saklar ganda type Wide Series Panasonic', volume: 14.00, unit: 'pcs', unitPrice: 38000 },
        { name: 'Stop kontak CP type Wide Series Panasonic', volume: 38.00, unit: 'pcs', unitPrice: 36000 },
        { name: 'Panel box MCB 12 Group in-bow Schneider', volume: 2.00, unit: 'unit', unitPrice: 420000 },
        { name: 'MCB 1 Phase 10A / 16A Schneider Domae', volume: 12.00, unit: 'pcs', unitPrice: 65000 },
        { name: 'Lampu dinding eksterior waterproof fitting E27', volume: 8.00, unit: 'pcs', unitPrice: 185000 },
        { name: 'Exhaust fan plafon type 10" KDK', volume: 6.00, unit: 'unit', unitPrice: 420000 }
      ]
    },
    {
      code: 'O',
      name: 'PEKERJAAN PENGECATAN DINDING & PLAFON',
      items: [
        { name: 'Cat dinding interior 3 lapis Dulux Pentalite', volume: 960.00, unit: 'm2', unitPrice: 48000 },
        { name: 'Cat dinding eksterior 3 lapis Dulux Weathershield', volume: 190.00, unit: 'm2', unitPrice: 72000 },
        { name: 'Cat Plafon gypsum 3 lapis Dulux Catylac', volume: 380.00, unit: 'm2', unitPrice: 35000 },
        { name: 'Pekerjaan plamir dinding semen instan MU-290', volume: 1150.00, unit: 'm2', unitPrice: 18000 },
        { name: 'Pekerjaan sealer wall primer alkali resisting Dulux', volume: 1150.00, unit: 'm2', unitPrice: 22000 },
        { name: 'Cat permukaan beton ekspos / coating doff', volume: 85.00, unit: 'm2', unitPrice: 45000 },
        { name: 'Cat lis profil gypsum cat air putih', volume: 420.00, unit: 'm1', unitPrice: 12000 },
        { name: 'Pekerjaan amplas dinding & plafon halus', volume: 1530.00, unit: 'm2', unitPrice: 6500 },
        { name: 'Pekerjaan proteksi lantai dengan plastik cor', volume: 380.00, unit: 'm2', unitPrice: 4500 }
      ]
    },
    {
      code: 'P',
      name: 'PEKERJAAN WATERPROOFING & COATING',
      items: [
        { name: 'Waterproofing cementitious 2 lapis SikaTop-107 toilet', volume: 96.00, unit: 'm2', unitPrice: 95000 },
        { name: 'Waterproofing membrane bakar t=3mm Sika dak beton', volume: 45.00, unit: 'm2', unitPrice: 185000 },
        { name: 'Pekerjaan screed pelindung waterproofing t=3cm', volume: 45.00, unit: 'm2', unitPrice: 65000 },
        { name: 'Waterproofing polyurethane coating area luar', volume: 64.00, unit: 'm2', unitPrice: 120000 },
        { name: 'Coating batu alam gloss/doff Arca', volume: 32.00, unit: 'm2', unitPrice: 48000 },
        { name: 'Kawat ayam kassa penguat sudut waterproofing', volume: 120.00, unit: 'm1', unitPrice: 15000 },
        { name: 'Pekerjaan uji rendam air toilet 1x24 jam', volume: 6.00, unit: 'ruang', unitPrice: 150000 },
        { name: 'Pekerjaan injeksi polyurethane kebocoran beton', volume: 1.00, unit: 'lot', unitPrice: 2500000 },
        { name: 'Pembersihan permukaan beton sebelum coating', volume: 205.00, unit: 'm2', unitPrice: 8500 }
      ]
    },
    {
      code: 'Q',
      name: 'PEKERJAAN RAILING & BESI',
      items: [
        { name: 'Railing tangga besi hollow hitam + handrail kayu', volume: 18.50, unit: 'm1', unitPrice: 950000 },
        { name: 'Railing balkon kaca tempered 10mm + frame stainless 304', volume: 12.40, unit: 'm1', unitPrice: 1850000 },
        { name: 'Pekerjaan kanopi kaca tempered 8mm + rangka besi WF', volume: 24.00, unit: 'm2', unitPrice: 1450000 },
        { name: 'Tangga putar besi servis dia. 100cm t=3m', volume: 1.00, unit: 'unit', unitPrice: 4500000 },
        { name: 'Grating besi saluran air selokan luar 20x100', volume: 15.00, unit: 'pcs', unitPrice: 180000 },
        { name: 'Pekerjaan cat duco railing tangga & balkon', volume: 1.00, unit: 'lot', unitPrice: 2800000 },
        { name: 'Cat anti karat zinc chromate untuk kanopi', volume: 48.00, unit: 'm2', unitPrice: 25000 },
        { name: 'Dynabolt M12 x 100 untuk dudukan railing', volume: 85.00, unit: 'pcs', unitPrice: 12000 },
        { name: 'Las sambungan konstruksi kanopi besi', volume: 1.00, unit: 'lot', unitPrice: 1500000 }
      ]
    },
    {
      code: 'R',
      name: 'PEKERJAAN FACADE & CLADDING',
      items: [
        { name: 'Aluminium Composite Panel (ACP) PVDF 0.3mm Seven', volume: 68.00, unit: 'm2', unitPrice: 680000 },
        { name: 'Rangka ACP besi siku 40x40x3 & hollow 40x40', volume: 420.00, unit: 'kg', unitPrice: 24000 },
        { name: 'Curtain wall kaca stopsol 6mm + frame aluminium', volume: 24.50, unit: 'm2', unitPrice: 1650000 },
        { name: 'Sealant silicone neutral Dowsil keliling ACP/kaca', volume: 240.00, unit: 'm1', unitPrice: 28000 },
        { name: 'Scaffolding sewa & pemasangan untuk facade', volume: 1.00, unit: 'lot', unitPrice: 3500000 },
        { name: 'Bracket besi tebal 6mm + angkur untuk ACP', volume: 180.00, unit: 'pcs', unitPrice: 45000 },
        { name: 'Pekerjaan kisi-kisi louvre aluminium shading', volume: 18.50, unit: 'm2', unitPrice: 850000 },
        { name: 'Skrup roofing + dynabolt M10 untuk facade', volume: 1.00, unit: 'lot', unitPrice: 1200000 },
        { name: 'Pembersihan kaca & ACP area facade luar', volume: 111.00, unit: 'm2', unitPrice: 18000 }
      ]
    },
    {
      code: 'S',
      name: 'PEKERJAAN HALAMAN, PAGAR & LANSKAP',
      items: [
        { name: 'Pasang paving block press K-250 tebal 6cm grey', volume: 120.00, unit: 'm2', unitPrice: 135000 },
        { name: 'Urugan pasir bawah paving block t=5cm', volume: 6.00, unit: 'm3', unitPrice: 180000 },
        { name: 'Kanstin beton pembatas paving 15x30x40', volume: 45.00, unit: 'pcs', unitPrice: 48000 },
        { name: 'Pagar depan besi hollow minimalis t=1.5m sliding', volume: 12.00, unit: 'm2', unitPrice: 850000 },
        { name: 'Pondasi pagar beton cor & batu kali', volume: 1.00, unit: 'lot', unitPrice: 6500000 },
        { name: 'Pekerjaan taman rumput gajah mini + tanah subur', volume: 32.00, unit: 'm2', unitPrice: 75000 },
        { name: 'Pekerjaan pohon peneduh ketapang kencana H=3m', volume: 3.00, unit: 'pohon', unitPrice: 450000 },
        { name: 'Pasang batu alam susun sirih dinding pagar', volume: 18.50, unit: 'm2', unitPrice: 220000 },
        { name: 'Lampu taman tancap solar cell LED', volume: 6.00, unit: 'unit', unitPrice: 125000 }
      ]
    },
    {
      code: 'T',
      name: 'PEKERJAAN PEMBERSIHAN AKHIR',
      items: [
        { name: 'Pembersihan kaca jendela & pintu seluruh bangunan', volume: 1.00, unit: 'ls', unitPrice: 1500000 },
        { name: 'Pembersihan noda semen keramik lantai & dinding', volume: 420.00, unit: 'm2', unitPrice: 7500 },
        { name: 'Pembuangan puing sisa konstruksi keluar proyek', volume: 8.00, unit: 'rit', unitPrice: 450000 },
        { name: 'General cleaning menggunakan poles lantai', volume: 1.00, unit: 'lot', unitPrice: 3500000 },
        { name: 'Penyemprotan disinfektan / anti rayap pra-huni', volume: 380.00, unit: 'm2', unitPrice: 12000 },
        { name: 'Pembersihan area luar & saluran air selokan', volume: 1.00, unit: 'ls', unitPrice: 1200000 },
        { name: 'Perapihan sisa cat & plesteran yang menempel', volume: 1.00, unit: 'ls', unitPrice: 950000 },
        { name: 'Sewa dump truck pembuangan sampah proyek', volume: 2.00, unit: 'hari', unitPrice: 850000 },
        { name: 'Pekerjaan perapihan jalan umum rusak akibat proyek', volume: 1.00, unit: 'ls', unitPrice: 2500000 }
      ]
    },
    {
      code: 'U',
      name: 'PEKERJAAN MOBILISASI & K3',
      items: [
        { name: 'Mobilisasi dan demobilisasi peralatan berat', volume: 1.00, unit: 'ls', unitPrice: 5000000 },
        { name: 'Sewa direksikit & barak pekerja kontainer 20ft', volume: 6.00, unit: 'bulan', unitPrice: 3500000 },
        { name: 'Sewa toilet portable proyek + sedot berkala', volume: 6.00, unit: 'bulan', unitPrice: 1200000 },
        { name: 'Penyediaan air kerja & listrik kerja', volume: 6.00, unit: 'bulan', unitPrice: 1800000 },
        { name: 'Penyediaan APD (Helm, Rompi, Sepatu Boot) pekerja', volume: 25.00, unit: 'set', unitPrice: 150000 },
        { name: 'Penyediaan kotak P3K & rambu-rambu K3', volume: 1.00, unit: 'set', unitPrice: 1200000 },
        { name: 'Pekerjaan pembuatan shop drawing & as-built drawing', volume: 1.00, unit: 'lot', unitPrice: 4500000 },
        { name: 'Sewa scaffolding per set/bulan', volume: 80.00, unit: 'set', unitPrice: 35000 },
        { name: 'Biaya admin, koordinasi lingkungan & ijin lingkungan', volume: 1.00, unit: 'ls', unitPrice: 1000000 }
      ]
    }
  ];

  const targetBToU = 954247569.57; // 995,971,307.58 - 41,723,738.01

  // Compute current raw sum of B to U
  let rawSumBToU = 0;
  rawSectionsBToU.forEach(sec => {
    sec.items.forEach(it => {
      rawSumBToU += it.volume * it.unitPrice;
    });
  });

  const factor = targetBToU / rawSumBToU;

  let currentSumBToU = 0;
  const scaledSections = rawSectionsBToU.map((sec, secIdx) => {
    const isLastSec = secIdx === rawSectionsBToU.length - 1;
    const scaledItems = sec.items.map((it, itIdx) => {
      const isLastItem = isLastSec && itIdx === sec.items.length - 1;
      if (isLastItem) {
        return { ...it }; // Adjusted separately later
      }
      const newPrice = Math.round((it.unitPrice * factor) * 100) / 100;
      currentSumBToU += it.volume * newPrice;
      return {
        ...it,
        unitPrice: newPrice
      };
    });
    return {
      ...sec,
      items: scaledItems
    };
  });

  // Adjust the very last item of Section U to make the total exact
  const lastSec = scaledSections[scaledSections.length - 1];
  const lastItemIdx = lastSec.items.length - 1;
  const lastItem = lastSec.items[lastItemIdx];
  const neededLastItemPrice = targetBToU - currentSumBToU;
  lastSec.items[lastItemIdx] = {
    ...lastItem,
    unitPrice: Math.round(neededLastItemPrice * 100) / 100
  };

  // Flatten into row format
  const flattened = [];
  
  // Section A
  flattened.push({
    id: `sec-${sectionA.code}`,
    type: 'section',
    code: sectionA.code,
    name: sectionA.name
  });
  sectionA.items.forEach((it, idx) => {
    flattened.push({
      id: `item-${sectionA.code}-${idx}`,
      type: 'item',
      sectionCode: sectionA.code,
      no: idx + 1,
      name: it.name,
      volume: it.volume,
      unit: it.unit,
      unitPrice: it.unitPrice
    });
  });

  // Sections B to U
  scaledSections.forEach(sec => {
    flattened.push({
      id: `sec-${sec.code}`,
      type: 'section',
      code: sec.code,
      name: sec.name
    });
    sec.items.forEach((it, idx) => {
      flattened.push({
        id: `item-${sec.code}-${idx}`,
        type: 'item',
        sectionCode: sec.code,
        no: idx + 1,
        name: it.name,
        volume: it.volume,
        unit: it.unit,
        unitPrice: it.unitPrice
      });
    });
  });

  return flattened;
};

const Anggaran = () => {
  const queryParams = useMemo(() => new URLSearchParams(window.location.search), []);
  const projectId = queryParams.get('id') || '1';

  const [rows, setRows] = useState(() => {
    const saved = localStorage.getItem(`estimator_uploaded_rows_${projectId}`);
    return saved ? JSON.parse(saved) : getInitialData();
  });

  const [activeProjectId, setActiveProjectId] = useState(projectId);

  if (projectId !== activeProjectId) {
    setActiveProjectId(projectId);
    const saved = localStorage.getItem(`estimator_uploaded_rows_${projectId}`);
    setRows(saved ? JSON.parse(saved) : getInitialData());
  }

  useEffect(() => {
    localStorage.setItem(`estimator_uploaded_rows_${activeProjectId}`, JSON.stringify(rows));
  }, [rows, activeProjectId]);
  
  // Controls state
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(10);
  const [searchQuery, setSearchQuery] = useState("");
  const [ppnRate, setPpnRate] = useState(0);
  
  // Modals state
  const [showAddModal, setShowAddModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [selectedRow, setSelectedRow] = useState(null);
  const [targetSectionCode, setTargetSectionCode] = useState("A");
  
  // Form states
  const [formData, setFormData] = useState({
    name: "",
    volume: 0,
    unit: "",
    unitPrice: 0
  });

  // Toast notification state
  const [toast, setToast] = useState({
    show: false,
    message: "",
    type: "success"
  });

  // Utility to show toasts
  const triggerToast = (message, type = "success") => {
    setToast({ show: true, message, type });
    setTimeout(() => {
      setToast(prev => ({ ...prev, show: false }));
    }, 3500);
  };

  // Reset to initial seed data
  const handleResetData = () => {
    if (window.confirm("Apakah Anda yakin ingin mengatur ulang semua data ke estimasi awal?")) {
      setRows(getInitialData());
      setCurrentPage(1);
      setSearchQuery("");
      setPpnRate(0);
      triggerToast("Data estimasi berhasil diatur ulang ke default!", "success");
    }
  };

  // Dynamic calculations based on current state
  const totalProjectPrice = useMemo(() => {
    return rows.reduce((sum, r) => {
      if (r.type === 'item') {
        return sum + (r.volume * r.unitPrice);
      }
      return sum;
    }, 0);
  }, [rows]);

  const ppnAmount = useMemo(() => {
    return totalProjectPrice * (ppnRate / 100);
  }, [totalProjectPrice, ppnRate]);

  const grandTotalPrice = useMemo(() => {
    return totalProjectPrice + ppnAmount;
  }, [totalProjectPrice, ppnAmount]);

  // Filter rows based on search
  const filteredRows = useMemo(() => {
    if (!searchQuery.trim()) return rows;
    
    const query = searchQuery.toLowerCase();
    const result = [];
    let currentSecHeader = null;
    let secHasMatchingItems = false;

    rows.forEach((row) => {
      if (row.type === 'section') {
        currentSecHeader = row;
        secHasMatchingItems = false;
      } else {
        const nameMatch = row.name.toLowerCase().includes(query);
        const sectionMatch = row.sectionCode.toLowerCase().includes(query);
        const unitMatch = row.unit.toLowerCase().includes(query);
        
        if (nameMatch || sectionMatch || unitMatch) {
          if (currentSecHeader && !secHasMatchingItems) {
            result.push(currentSecHeader);
            secHasMatchingItems = true;
          }
          result.push(row);
        }
      }
    });

    return result;
  }, [rows, searchQuery]);

  // Handle page boundaries when filter changes
  const totalItems = filteredRows.length;
  const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
  
  // Safe page index check
  const activePage = Math.min(currentPage, totalPages);
  
  const paginatedRows = useMemo(() => {
    const startIdx = (activePage - 1) * pageSize;
    return filteredRows.slice(startIdx, startIdx + pageSize);
  }, [filteredRows, activePage, pageSize]);

  // Calculate pagination range to render
  const paginationRange = useMemo(() => {
    if (totalPages <= 7) {
      return Array.from({ length: totalPages }, (_, i) => i + 1);
    }
    const range = [];
    if (activePage <= 4) {
      range.push(1, 2, 3, 4, 5, '...', totalPages);
    } else if (activePage >= totalPages - 3) {
      range.push(1, '...', totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages);
    } else {
      range.push(1, '...', activePage - 1, activePage, activePage + 1, '...', totalPages);
    }
    return range;
  }, [activePage, totalPages]);

  // CRUD handlers
  const handleOpenAddModal = (sectionCode) => {
    setTargetSectionCode(sectionCode);
    setFormData({ name: "", volume: 1, unit: "m2", unitPrice: 10000 });
    setShowAddModal(true);
  };

  const handleAddItem = (e) => {
    e.preventDefault();
    if (!formData.name.trim()) return;

    // Find the insert position: right after the section header or after the last item of that section
    const targetIdx = rows.findIndex(r => r.type === 'section' && r.code === targetSectionCode);
    if (targetIdx === -1) return;

    // Count how many items are currently in this section to set the correct item number (no)
    let sectionItemCount = 0;
    let insertIdx = targetIdx + 1;

    for (let i = targetIdx + 1; i < rows.length; i++) {
      if (rows[i].type === 'section') {
        insertIdx = i;
        break;
      }
      if (rows[i].type === 'item') {
        sectionItemCount++;
        insertIdx = i + 1; // Insert after the last item of this section
      }
    }

    const newItem = {
      id: `item-${targetSectionCode}-${Date.now()}`,
      type: 'item',
      sectionCode: targetSectionCode,
      no: sectionItemCount + 1,
      name: formData.name,
      volume: Number(formData.volume),
      unit: formData.unit,
      unitPrice: Number(formData.unitPrice)
    };

    const newRows = [...rows];
    newRows.splice(insertIdx, 0, newItem);

    // Reindex remaining item numbers in this section
    let itemNumber = 1;
    for (let i = targetIdx + 1; i < newRows.length; i++) {
      if (newRows[i].type === 'section') break;
      if (newRows[i].type === 'item') {
        newRows[i].no = itemNumber++;
      }
    }

    setRows(newRows);
    setShowAddModal(false);
    triggerToast(`Berhasil menambahkan "${formData.name}" ke Pekerjaan ${targetSectionCode}!`);
  };

  const handleOpenEditModal = (item) => {
    setSelectedRow(item);
    setFormData({
      name: item.name,
      volume: item.volume,
      unit: item.unit,
      unitPrice: item.unitPrice
    });
    setShowEditModal(true);
  };

  const handleEditItem = (e) => {
    e.preventDefault();
    if (!selectedRow) return;

    const newRows = rows.map(r => {
      if (r.id === selectedRow.id) {
        return {
          ...r,
          name: formData.name,
          volume: Number(formData.volume),
          unit: formData.unit,
          unitPrice: Number(formData.unitPrice)
        };
      }
      return r;
    });

    setRows(newRows);
    setShowEditModal(false);
    setSelectedRow(null);
    triggerToast(`Detail pekerjaan "${formData.name}" berhasil diubah.`);
  };

  const handleDeleteItem = (item) => {
    if (window.confirm(`Apakah Anda yakin ingin menghapus "${item.name}"?`)) {
      const targetIdx = rows.findIndex(r => r.id === item.id);
      if (targetIdx === -1) return;

      const sectionCode = item.sectionCode;

      // Filter out the item
      let newRows = rows.filter(r => r.id !== item.id);

      // Re-index item numbers in this specific section
      const sectionHeaderIdx = newRows.findIndex(r => r.type === 'section' && r.code === sectionCode);
      if (sectionHeaderIdx !== -1) {
        let currentNo = 1;
        for (let i = sectionHeaderIdx + 1; i < newRows.length; i++) {
          if (newRows[i].type === 'section') break;
          if (newRows[i].type === 'item') {
            newRows[i].no = currentNo++;
          }
        }
      }

      setRows(newRows);
      triggerToast(`Pekerjaan "${item.name}" berhasil dihapus.`, "warning");
    }
  };

  const handleDeleteSection = (section) => {
    if (window.confirm(`Apakah Anda yakin ingin menghapus seluruh bagian "${section.code}. ${section.name}"?`)) {
      // Filter out the section header and all items with sectionCode === section.code
      const newRows = rows.filter(r => {
        if (r.id === section.id) return false;
        if (r.type === 'item' && r.sectionCode === section.code) return false;
        return true;
      });

      setRows(newRows);
      triggerToast(`Seluruh bagian "${section.code}. ${section.name}" berhasil dihapus.`, "warning");
    }
  };

  return (
    <div className="min-h-screen bg-[#f7faf8] pb-16 antialiased text-slate-800">
      <Navbar onResetData={handleResetData} />

      {/* Main Title Section */}
      <div className="max-w-[1240px] mx-auto px-4 mt-6">
        <div className="w-full bg-[#f1faf2] border border-[#dff3e1] rounded-lg py-4 px-6 flex items-center justify-center shadow-xs">
          <h1 className="text-lg md:text-xl font-bold tracking-wider text-emerald-950 uppercase select-none">
            ESTIMASI PROYEK ANDA
          </h1>
        </div>
      </div>

      {/* Main Workspace Container */}
      <main className="max-w-[1240px] mx-auto px-4 mt-6">
        <div className="bg-white rounded-xl shadow-xs border border-slate-100 p-6">
          
          {/* Controls Bar */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-100 mb-5">
            {/* Left Controls: Page size */}
            <div className="flex items-center gap-2">
              <span className="text-[13px] text-slate-600 font-medium">Data per Halaman:</span>
              <div className="relative">
                <select 
                  value={pageSize}
                  onChange={(e) => {
                    setPageSize(Number(e.target.value));
                    setCurrentPage(1);
                  }}
                  className="Anggaranearance-none bg-white border border-slate-200 rounded-md py-1.5 pl-3 pr-8 text-[13px] font-semibold text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 cursor-pointer shadow-2xs hover:border-slate-300 transition-colors"
                >
                  <option value={5}>5</option>
                  <option value={10}>10</option>
                  <option value={20}>20</option>
                  <option value={50}>50</option>
                </select>
                <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                  <Icons.ChevronDown />
                </div>
              </div>
            </div>

            {/* Right Controls: Search bar */}
            <div className="flex items-center gap-2.5 w-full sm:w-auto max-w-xs">
              <span className="text-[13px] text-slate-600 font-medium whitespace-nowrap">Cari Data:</span>
              <div className="relative w-full">
                <input 
                  type="text"
                  placeholder="Masukan kata kunci..."
                  value={searchQuery}
                  onChange={(e) => {
                    setSearchQuery(e.target.value);
                    setCurrentPage(1);
                  }}
                  className="w-full bg-white border border-slate-200 rounded-md py-1.5 pl-3 pr-9 text-[13px] text-slate-700 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 shadow-2xs transition-colors"
                />
                <div className="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                  <Icons.Search className="w-4 h-4" />
                </div>
              </div>
            </div>
          </div>

          {/* Table Container */}
          <div className="overflow-x-auto rounded-lg border border-slate-100 shadow-3xs mb-6">
            <table className="w-full border-collapse text-left text-[13px]">
              <thead>
                <tr className="bg-[#009624] text-white font-semibold">
                  <th scope="col" className="py-3.5 px-4 text-center w-12 select-none">No.</th>
                  <th scope="col" className="py-3.5 px-4 text-left min-w-[280px]">Uraian Pekerjaan</th>
                  <th scope="col" className="py-3.5 px-4 text-right w-24">Volume</th>
                  <th scope="col" className="py-3.5 px-4 text-center w-20">Satuan</th>
                  <th scope="col" className="py-3.5 px-4 text-center w-28">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {paginatedRows.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="py-12 text-center text-slate-400 font-medium">
                      Tidak ada data yang ditemukan.
                    </td>
                  </tr>
                ) : (
                  paginatedRows.map((row) => {
                    if (row.type === 'section') {
                      return (
                        <tr key={row.id} className="bg-slate-50/70 hover:bg-slate-100/50 transition-colors font-bold text-slate-800 group">
                          <td className="py-3 px-4 text-center select-none">{row.code}</td>
                          <td colSpan={3} className="py-3 px-4 uppercase tracking-wide text-[12.5px] text-emerald-950">
                            {row.name}
                          </td>
                          <td className="py-3 px-4 text-center">
                            <div className="inline-flex items-center gap-1.5 bg-[#c9f0cc] px-2.5 py-1 rounded-full shadow-2xs">
                              {/* Add item to section */}
                              <button 
                                onClick={() => handleOpenAddModal(row.code)}
                                className="w-6 h-6 rounded-full bg-[#009624] hover:bg-emerald-700 text-white flex items-center justify-center transition-transform hover:scale-105"
                                title={`Tambah pekerjaan ke Bagian ${row.code}`}
                              >
                                <Icons.Plus className="w-3.5 h-3.5 stroke-[3]" />
                              </button>
                              {/* Delete entire section */}
                              <button 
                                onClick={() => handleDeleteSection(row)}
                                className="w-6 h-6 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center transition-transform hover:scale-105"
                                title={`Hapus seluruh Bagian ${row.code}`}
                              >
                                <Icons.Trash className="w-3.5 h-3.5" />
                              </button>
                            </div>
                          </td>
                        </tr>
                      );
                    }

                    // Otherwise it is an item row
                    const itemPrice = row.volume * (row.unitPrice || 0);
                    const itemPercentage = totalProjectPrice > 0 ? (itemPrice / totalProjectPrice) * 100 : 0;
                    
                    return (
                      <tr key={row.id} className="hover:bg-slate-50/50 transition-all group duration-150">
                        {/* No. */}
                        <td className="py-3 px-4 text-center text-slate-400 select-none">{row.no}</td>
                        {/* Uraian Pekerjaan */}
                        <td className="py-3 px-4 max-w-[400px]">
                          <div className="flex items-start gap-2">
                            <div className="flex-1 min-w-0">
                              <span className="font-medium text-slate-800 break-words">{row.name}</span>
                            </div>
                          </div>
                        </td>
                        {/* Volume */}
                        <td className="py-3 px-4 text-right tabular-nums font-medium text-slate-700">
                          {formatNumber(row.volume)}
                        </td>
                        {/* Satuan */}
                        <td className="py-3 px-4 text-center text-slate-500 font-semibold">{row.unit}</td>
                        {/* Aksi */}
                        <td className="py-3 px-4 text-center">
                          <div className="inline-flex items-center gap-1.5 bg-[#d2f3d5] px-2.5 py-1 rounded-full shadow-3xs group-hover:bg-[#c3eec7] transition-all">
                            {/* View / Edit button */}
                            <button 
                              onClick={() => handleOpenEditModal(row)}
                              className="w-6 h-6 rounded-full bg-[#009624] hover:bg-emerald-700 text-white flex items-center justify-center transition-transform hover:scale-105"
                              title="Ubah detail pekerjaan"
                            >
                              <Icons.Edit className="w-3.5 h-3.5" />
                            </button>
                            
                            {/* Actions dropdown pill */}
                            <div className="relative group/dropdown">
                              <button 
                                className="w-9 h-6 rounded-full bg-[#009624] hover:bg-emerald-700 text-white flex items-center justify-center gap-0.5 px-1.5 transition-transform hover:scale-105"
                                title="Opsi lainnya"
                              >
                                <Icons.Grid className="w-3 h-3" />
                                <Icons.ChevronDown className="w-2.5 h-2.5" />
                              </button>
                              
                              {/* Floating Dropdown options */}
                              <div className="absolute right-0 bottom-full mb-2 w-40 bg-white border border-slate-150 rounded-lg shadow-lg py-1 z-30 hidden group-hover/dropdown:block origin-bottom-right transition-all">
                                <button 
                                  onClick={() => handleOpenEditModal(row)}
                                  className="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center gap-2 text-[12.5px] text-slate-700 font-medium"
                                >
                                  <Icons.Edit className="w-3.5 h-3.5 text-emerald-600" />
                                  Edit Item
                                </button>
                                <button 
                                  onClick={() => handleDeleteItem(row)}
                                  className="w-full text-left px-3.5 py-2 hover:bg-red-50 text-red-600 flex items-center gap-2 text-[12.5px] font-semibold border-t border-slate-50"
                                >
                                  <Icons.Trash className="w-3.5 h-3.5" />
                                  Hapus Item
                                </button>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                    );
                  })
                )}

                {/* Table Footer / Summary Rows (Removed) */}
              </tbody>
            </table>
          </div>

          {/* Footer Controls & Pagination */}
          {totalPages > 0 && (
            <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-100">
              {/* Pagination Info */}
              <div className="text-[12.5px] text-slate-500 font-medium">
                Menampilkan <span className="font-semibold text-slate-700">{Math.min(filteredRows.length, (activePage - 1) * pageSize + 1)}</span> - <span className="font-semibold text-slate-700">{Math.min(filteredRows.length, activePage * pageSize)}</span> dari <span className="font-semibold text-slate-700">{filteredRows.length}</span> baris
              </div>

              {/* Pagination Buttons */}
              <div className="flex items-center gap-1">
                {/* Previous Page */}
                <button
                  disabled={activePage === 1}
                  onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                  className="px-3 py-1.5 rounded border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed text-[12.5px] font-semibold shadow-3xs cursor-pointer transition-colors"
                >
                  Sebelumnya
                </button>

                {/* Page Numbers */}
                {paginationRange.map((page, idx) => {
                  if (page === '...') {
                    return (
                      <span key={`dots-${idx}`} className="px-2 text-slate-400 select-none">
                        ...
                      </span>
                    );
                  }

                  const isActive = page === activePage;
                  return (
                    <button
                      key={`page-${page}`}
                      onClick={() => setCurrentPage(page)}
                      className={`w-8 h-8 rounded-full flex items-center justify-center font-bold text-[12.5px] transition-all cursor-pointer ${
                        isActive
                          ? 'bg-[#009624] text-white shadow-xs'
                          : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300'
                      }`}
                    >
                      {page}
                    </button>
                  );
                })}

                {/* Next Page */}
                <button
                  disabled={activePage === totalPages}
                  onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
                  className="px-3 py-1.5 rounded border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed text-[12.5px] font-semibold shadow-3xs cursor-pointer transition-colors"
                >
                  Berikutnya
                </button>
              </div>
            </div>
          )}

        </div>
      </main>

      {/* Floating Toast System */}
      {toast.show && (
        <div className={`fixed bottom-5 right-5 z-50 flex items-center gap-2.5 px-4.5 py-3 rounded-lg shadow-xl text-white font-medium transition-all transform translate-y-0 animate-bounce duration-300 ${
          toast.type === 'success' 
            ? 'bg-emerald-600 border border-emerald-500' 
            : toast.type === 'warning'
              ? 'bg-amber-600 border border-amber-500'
              : 'bg-red-600 border border-red-500'
        }`}>
          <Icons.Info className="w-5 h-5" />
          <span className="text-[13px]">{toast.message}</span>
        </div>
      )}

      {/* Modal - Add Item */}
      {showAddModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto animate-fade-in">
          <div className="bg-white rounded-xl shadow-2xl border border-slate-100 max-w-md w-full overflow-hidden transform scale-100 transition-all">
            {/* Modal Header */}
            <div className="bg-emerald-600 text-white px-5 py-4 flex items-center justify-between">
              <h3 className="font-bold text-[14.5px] uppercase tracking-wide">
                Tambah Pekerjaan (Bagian {targetSectionCode})
              </h3>
              <button 
                onClick={() => setShowAddModal(false)}
                className="p-1 rounded-full hover:bg-emerald-700 text-emerald-100 hover:text-white transition-colors"
              >
                <Icons.X />
              </button>
            </div>
            
            {/* Modal Form */}
            <form onSubmit={handleAddItem} className="p-5 space-y-4">
              <div>
                <label className="block text-[12.5px] font-semibold text-slate-600 mb-1.5">
                  Uraian Pekerjaan <span className="text-red-500">*</span>
                </label>
                <input 
                  type="text"
                  required
                  placeholder="Misal: Pemasangan keramik lantai..."
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full bg-white border border-slate-200 rounded-md py-2 px-3 text-[13px] text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-[12.5px] font-semibold text-slate-600 mb-1.5">
                    Volume <span className="text-red-500">*</span>
                  </label>
                  <input 
                    type="number"
                    required
                    step="0.01"
                    min="0.01"
                    value={formData.volume}
                    onChange={(e) => setFormData({ ...formData, volume: Number(e.target.value) })}
                    className="w-full bg-white border border-slate-200 rounded-md py-2 px-3 text-[13px] text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                  />
                </div>
                <div>
                  <label className="block text-[12.5px] font-semibold text-slate-600 mb-1.5">
                    Satuan <span className="text-red-500">*</span>
                  </label>
                  <input 
                    type="text"
                    required
                    placeholder="m2, m3, unit, dll."
                    value={formData.unit}
                    onChange={(e) => setFormData({ ...formData, unit: e.target.value })}
                    className="w-full bg-white border border-slate-200 rounded-md py-2 px-3 text-[13px] text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                  />
                </div>
              </div>

              {/* Harga Satuan (Removed) */}

              <div className="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button 
                  type="button"
                  onClick={() => setShowAddModal(false)}
                  className="px-4 py-2 border border-slate-200 rounded-md text-slate-700 hover:bg-slate-50 text-[12.5px] font-semibold transition-colors cursor-pointer"
                >
                  Batal
                </button>
                <button 
                  type="submit"
                  className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-[12.5px] font-semibold shadow-sm transition-colors cursor-pointer"
                >
                  Simpan Pekerjaan
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal - Edit Item */}
      {showEditModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto animate-fade-in">
          <div className="bg-white rounded-xl shadow-2xl border border-slate-100 max-w-md w-full overflow-hidden transform scale-100 transition-all">
            {/* Modal Header */}
            <div className="bg-emerald-600 text-white px-5 py-4 flex items-center justify-between">
              <h3 className="font-bold text-[14.5px] uppercase tracking-wide">
                Ubah Detail Pekerjaan
              </h3>
              <button 
                onClick={() => {
                  setShowEditModal(false);
                  setSelectedRow(null);
                }}
                className="p-1 rounded-full hover:bg-emerald-700 text-emerald-100 hover:text-white transition-colors"
              >
                <Icons.X />
              </button>
            </div>
            
            {/* Modal Form */}
            <form onSubmit={handleEditItem} className="p-5 space-y-4">
              <div>
                <label className="block text-[12.5px] font-semibold text-slate-600 mb-1.5">
                  Uraian Pekerjaan <span className="text-red-500">*</span>
                </label>
                <input 
                  type="text"
                  required
                  placeholder="Misal: Pemasangan keramik lantai..."
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full bg-white border border-slate-200 rounded-md py-2 px-3 text-[13px] text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-[12.5px] font-semibold text-slate-600 mb-1.5">
                    Volume <span className="text-red-500">*</span>
                  </label>
                  <input 
                    type="number"
                    required
                    step="0.01"
                    min="0.01"
                    value={formData.volume}
                    onChange={(e) => setFormData({ ...formData, volume: Number(e.target.value) })}
                    className="w-full bg-white border border-slate-200 rounded-md py-2 px-3 text-[13px] text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                  />
                </div>
                <div>
                  <label className="block text-[12.5px] font-semibold text-slate-600 mb-1.5">
                    Satuan <span className="text-red-500">*</span>
                  </label>
                  <input 
                    type="text"
                    required
                    placeholder="m2, m3, unit, dll."
                    value={formData.unit}
                    onChange={(e) => setFormData({ ...formData, unit: e.target.value })}
                    className="w-full bg-white border border-slate-200 rounded-md py-2 px-3 text-[13px] text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                  />
                </div>
              </div>

              {/* Harga Satuan (Removed) */}

              <div className="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button 
                  type="button"
                  onClick={() => {
                    setShowEditModal(false);
                    setSelectedRow(null);
                  }}
                  className="px-4 py-2 border border-slate-200 rounded-md text-slate-700 hover:bg-slate-50 text-[12.5px] font-semibold transition-colors cursor-pointer"
                >
                  Batal
                </button>
                <button 
                  type="submit"
                  className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-[12.5px] font-semibold shadow-sm transition-colors cursor-pointer"
                >
                  Simpan Perubahan
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

    </div>
  );
};

export default Anggaran;