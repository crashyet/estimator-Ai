const API_URL = import.meta.env.VITE_API_URL;


export const mapToFrontendFormat = (projectName, clientName, llmData) => {
  // If the backend response is already pre-formatted in the resoon_api.json structure
  if (llmData && llmData.project && Array.isArray(llmData.items) && llmData.items.length > 0 && llmData.items[0].type) {
    return {
      project: {
        title: llmData.project.title || projectName,
        client: llmData.project.client || clientName,
        budget: 0,
        status: llmData.project.status || "Perencanaan"
      },
      anggaran: llmData.items
    };
  }

  const sections = {
    A: { name: "PEKERJAAN PERSIAPAN", items: [] },
    B: { name: "PEKERJAAN TANAH DAN PONDASI", items: [] },
    C: { name: "PEKERJAAN STRUKTUR BETON", items: [] },
    D: { name: "PEKERJAAN DINDING, KUSEN & FINISHING", items: [] },
    E: { name: "PEKERJAAN ATAP & PLAFON", items: [] },
    F: { name: "PEKERJAAN INSTALASI & ELEKTRIKAL", items: [] },
    G: { name: "PEKERJAAN LAIN-LAIN / K3", items: [] }
  };
  
  const items = llmData.items || [];
  
  items.forEach(item => {
    const code = String(item.kode_ahsp || "").toUpperCase();
    const uraian = item.uraian || "";
    const satuan = item.satuan || "m2";
    let volume = parseFloat(item.volume_est || 1);
    if (isNaN(volume)) volume = 1;
    
    const uraianLower = uraian.toLowerCase();
    
    let targetSec = "G";
    if (code.startsWith("A") || ["bersih", "pagar", "ukur", "papan", "persiapan", "sondir", "hand boring"].some(kw => uraianLower.includes(kw))) {
      targetSec = "A";
    } else if (code.startsWith("B") || ["galian", "pondasi", "urug", "pasir", "tanah", "pile", "batu belah"].some(kw => uraianLower.includes(kw))) {
      targetSec = "B";
    } else if (code.startsWith("C") || ["beton", "sloof", "kolom", "balok", "plat", "tangga", "bekisting", "besi"].some(kw => uraianLower.includes(kw))) {
      targetSec = "C";
    } else if (code.startsWith("D") || ["dinding", "plesteran", "acian", "bata", "hebel", "roster", "kusen", "pintu", "jendela", "kaca"].some(kw => uraianLower.includes(kw))) {
      targetSec = "D";
    } else if (code.startsWith("E") || ["atap", "genteng", "nok", "lisplank", "plafon", "gypsum", "grc", "hollow"].some(kw => uraianLower.includes(kw))) {
      targetSec = "E";
    } else if (code.startsWith("F") || ["pipa", "air", "septic", "biofil", "wastafel", "closet", "kran", "shower", "listrik", "stop kontak", "lampu", "mcb", "panel"].some(kw => uraianLower.includes(kw))) {
      targetSec = "F";
    }
    
    sections[targetSec].items.push({
      name: uraian,
      volume: volume,
      unit: satuan
    });
  });
  
  const anggaranRows = [];
  
  ["A", "B", "C", "D", "E", "F", "G"].forEach(secCode => {
    const secInfo = sections[secCode];
    if (secInfo.items.length === 0) return;
    
    anggaranRows.push({
      id: `sec-${secCode}`,
      type: "section",
      code: secCode,
      name: secInfo.name
    });
    
    secInfo.items.forEach((item, itemIdx) => {
      const itemId = `item-${secCode}-${itemIdx}`;
      const vol = item.volume;
      
      anggaranRows.push({
        id: itemId,
        type: "item",
        sectionCode: secCode,
        no: itemIdx + 1,
        name: item.name,
        volume: vol,
        unit: item.unit
      });
    });
  });
  
  return {
    project: {
      title: projectName,
      client: clientName,
      budget: 0,
      status: "Perencanaan"
    },
    anggaran: anggaranRows
  };
};

export const analyzeDED = async (projectName, clientName, file) => {
  const data = new FormData();
  data.append('name', projectName);
  data.append('client', clientName);
  data.append('ded_file', file);

  const response = await fetch(API_URL, {
    method: 'POST',
    body: data
  });

  if (!response.ok) {
    throw new Error(`Server returned error ${response.status}: ${response.statusText}`);
  }

  const result = await response.json();
  return mapToFrontendFormat(projectName, clientName, result);
};
