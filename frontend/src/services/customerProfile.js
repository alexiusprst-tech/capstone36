const fields = ['code', 'name', 'orgFullName', 'orgShortName', 'type', 'province', 'city', 'address', 'letterheadAddress', 'letterheadCity', 'orgPhone', 'phone', 'fax', 'website', 'email', 'postalCode', 'governmentName', 'educationOffice', 'logoDataUrl'];

/**
 * Memperluas singkatan jenjang/nama sekolah untuk kop surat resmi
 * (contoh: SMA Negeri 2 Bogor -> SEKOLAH MENENGAH ATAS NEGERI 2 BOGOR)
 */
export function expandSchoolName(name) {
  if (!name || typeof name !== 'string') return '';
  let str = name.trim();

  // Jika sudah dimulai dengan nama panjang resmi, jangan digandakan
  if (/^(?:SEKOLAH\s+MENENGAH|SEKOLAH\s+DASAR|SEKOLAH\s+LUAR\s+BIASA|MADRASAH)\b/i.test(str)) {
    return str.replace(/\bNEGRI\b/gi, 'NEGERI').replace(/\s+/g, ' ').trim();
  }

  // 1. SMAN / SMA NEGERI / SMA NEGRI / SMA N / SMA
  if (/\bSMAN\b/i.test(str)) {
    str = str.replace(/\bSMAN\b/gi, 'SEKOLAH MENENGAH ATAS NEGERI');
  } else if (/\bSMA\s+(?:NEGERI|NEGRI|N\.?)\b/i.test(str)) {
    str = str.replace(/\bSMA\s+(?:NEGERI|NEGRI|N\.?)\b/gi, 'SEKOLAH MENENGAH ATAS NEGERI');
  } else if (/\bSMA\b/i.test(str)) {
    str = str.replace(/\bSMA\b/gi, 'SEKOLAH MENENGAH ATAS');
  }

  // 2. SMKN / SMK NEGERI / SMK NEGRI / SMK N / SMK
  else if (/\bSMKN\b/i.test(str)) {
    str = str.replace(/\bSMKN\b/gi, 'SEKOLAH MENENGAH KEJURUAN NEGERI');
  } else if (/\bSMK\s+(?:NEGERI|NEGRI|N\.?)\b/i.test(str)) {
    str = str.replace(/\bSMK\s+(?:NEGERI|NEGRI|N\.?)\b/gi, 'SEKOLAH MENENGAH KEJURUAN NEGERI');
  } else if (/\bSMK\b/i.test(str)) {
    str = str.replace(/\bSMK\b/gi, 'SEKOLAH MENENGAH KEJURUAN');
  }

  // 3. SMPN / SMP NEGERI / SMP NEGRI / SMP N / SMP
  else if (/\bSMPN\b/i.test(str)) {
    str = str.replace(/\bSMPN\b/gi, 'SEKOLAH MENENGAH PERTAMA NEGERI');
  } else if (/\bSMP\s+(?:NEGERI|NEGRI|N\.?)\b/i.test(str)) {
    str = str.replace(/\bSMP\s+(?:NEGERI|NEGRI|N\.?)\b/gi, 'SEKOLAH MENENGAH PERTAMA NEGERI');
  } else if (/\bSMP\b/i.test(str)) {
    str = str.replace(/\bSMP\b/gi, 'SEKOLAH MENENGAH PERTAMA');
  }

  // 4. SDN / SD NEGERI / SD NEGRI / SD N / SD
  else if (/\bSDN\b/i.test(str)) {
    str = str.replace(/\bSDN\b/gi, 'SEKOLAH DASAR NEGERI');
  } else if (/\bSD\s+(?:NEGERI|NEGRI|N\.?)\b/i.test(str)) {
    str = str.replace(/\bSD\s+(?:NEGERI|NEGRI|N\.?)\b/gi, 'SEKOLAH DASAR NEGERI');
  } else if (/\bSD\b/i.test(str)) {
    str = str.replace(/\bSD\b/gi, 'SEKOLAH DASAR');
  }

  // 5. SLBN / SLB NEGERI / SLB
  else if (/\bSLBN\b/i.test(str)) {
    str = str.replace(/\bSLBN\b/gi, 'SEKOLAH LUAR BIASA NEGERI');
  } else if (/\bSLB\s+(?:NEGERI|NEGRI|N\.?)\b/i.test(str)) {
    str = str.replace(/\bSLB\s+(?:NEGERI|NEGRI|N\.?)\b/gi, 'SEKOLAH LUAR BIASA NEGERI');
  } else if (/\bSLB\b/i.test(str)) {
    str = str.replace(/\bSLB\b/gi, 'SEKOLAH LUAR BIASA');
  }

  // 6. MAN / MA NEGERI / MA
  else if (/\bMAN\b/i.test(str)) {
    str = str.replace(/\bMAN\b/gi, 'MADRASAH ALIYAH NEGERI');
  } else if (/\bMA\s+(?:NEGERI|NEGRI|N\.?)\b/i.test(str)) {
    str = str.replace(/\bMA\s+(?:NEGERI|NEGRI|N\.?)\b/gi, 'MADRASAH ALIYAH NEGERI');
  }

  // 7. MTsN / MTs NEGERI / MTs
  else if (/\bMTsN\b/i.test(str)) {
    str = str.replace(/\bMTsN\b/gi, 'MADRASAH TSANAWIYAH NEGERI');
  } else if (/\bMTs\s+(?:NEGERI|NEGRI|N\.?)\b/i.test(str)) {
    str = str.replace(/\bMTs\s+(?:NEGERI|NEGRI|N\.?)\b/gi, 'MADRASAH TSANAWIYAH NEGERI');
  }

  // 8. MIN / MI NEGERI / MI
  else if (/\bMIN\b/i.test(str)) {
    str = str.replace(/\bMIN\b/gi, 'MADRASAH IBTIDAIYAH NEGERI');
  } else if (/\bMI\s+(?:NEGERI|NEGRI|N\.?)\b/i.test(str)) {
    str = str.replace(/\bMI\s+(?:NEGERI|NEGRI|N\.?)\b/gi, 'MADRASAH IBTIDAIYAH NEGERI');
  }

  return str.replace(/\s+/g, ' ').trim();
}

export function customerProfile(client = {}) {
  const profile = Object.fromEntries(fields.map(field => [field, typeof client[field] === 'string' ? client[field].trim() : '']));
  if (profile.orgFullName) {
    profile.orgFullName = expandSchoolName(profile.orgFullName);
  } else if (profile.name) {
    profile.orgFullName = expandSchoolName(profile.name);
  }
  return profile;
}

export function findProjectCustomer(project, clients) {
  const normalize = value => String(value || '').toLowerCase().replace(/\bnegeri\b/g, 'negri').replace(/[^a-z0-9]/g, '');
  const rawName = normalize(project?.customer_name);
  const expName = normalize(expandSchoolName(project?.customer_name));
  if (!rawName) return null;
  const matches = (clients || []).filter(client => {
    const aliases = [
      client.name,
      client.orgFullName,
      client.orgShortName,
      expandSchoolName(client.name),
      expandSchoolName(client.orgFullName),
      expandSchoolName(client.orgShortName),
    ].filter(Boolean);
    return aliases.some(alias => {
      const norm = normalize(alias);
      return norm === rawName || norm === expName;
    });
  });
  return matches.find(client => client.code === project.customer_code) || (matches.length === 1 ? matches[0] : null);
}
