/**
 * API Service for CodeIgniter 4 Backend & AI Engine Integration
 * Matches documentation in docs/API_BACKEND_CI4.md
 */

export const getBackendBaseUrl = () => {
  if (import.meta.env.VITE_BACKEND_BASE_URL) {
    return import.meta.env.VITE_BACKEND_BASE_URL.replace(/\/$/, '');
  }
  if (import.meta.env.VITE_BACKEND_API_URL) {
    try {
      const url = new URL(import.meta.env.VITE_BACKEND_API_URL);
      return `${url.protocol}//${url.host}`;
    } catch (_e) {
      // Fallback if URL parsing fails
    }
  }
  return typeof window !== 'undefined' && window.location.hostname
    ? `http://${window.location.hostname}:8080`
    : 'http://localhost:8080';
};

const defaultHeaders = {
  'Content-Type': 'application/json',
  'Accept': 'application/json'
};

// Helper for HTTP requests with error parsing
const request = async (endpoint, options = {}) => {
  const baseUrl = getBackendBaseUrl();
  const url = endpoint.startsWith('http') ? endpoint : `${baseUrl}${endpoint}`;

  const config = {
    ...options,
    headers: {
      ...defaultHeaders,
      ...(options.headers || {})
    }
  };

  const response = await fetch(url, config);
  const contentType = response.headers.get('content-type') || '';
  let data;
  if (contentType.includes('application/json')) {
    try {
      data = await response.json();
    } catch (_e) {
      data = null;
    }
  } else {
    const rawText = await response.text();
    try {
      data = JSON.parse(rawText);
    } catch (_e) {
      data = rawText;
    }
  }

  if (!response.ok) {
    let message = `Request error (${response.status})`;
    if (typeof data === 'object' && data !== null) {
      const details = data.message || data.detail || data.error || data.title || '';
      if (details) {
        message = `${message}: ${details}`;
      }
      if (data.file && data.line) {
        message += ` [${data.file}:${data.line}]`;
      }
    } else if (typeof data === 'string' && data.length < 250) {
      message = `${message}: ${data}`;
    }
    const error = new Error(message);
    error.status = response.status;
    error.data = data;
    throw error;
  }

  return data;
};

// ==========================================
// 1. Modul Proyek (/api/projects)
// ==========================================

/**
 * Mengambil semua daftar proyek beserta total anggaran & info run terakhir
 * GET /api/projects
 */
export const fetchProjects = async () => {
  const res = await request('/api/projects');
  return res.data || [];
};

/**
 * Membuat proyek baru
 * POST /api/projects
 * @param {{ title: string, client: string, status?: string, summary?: string }} projectData
 */
export const createProject = async (projectData) => {
  const res = await request('/api/projects', {
    method: 'POST',
    body: JSON.stringify({
      title: projectData.title || projectData.name || 'Proyek Baru',
      client: projectData.client || 'Klien Internal',
      location: projectData.location || '',
      contractor_fee: projectData.contractor_fee !== undefined ? Number(projectData.contractor_fee) : 10.00,
      ppn: projectData.ppn !== undefined ? Number(projectData.ppn) : 11.00,
      status: projectData.status || 'Perencanaan',
      summary: projectData.summary || '',
      image: projectData.image || ''
    })
  });
  return res.data;
};

/**
 * Mengambil detail 1 proyek berdasarkan ID
 * GET /api/projects/{id}
 */
export const fetchProjectById = async (projectId) => {
  const res = await request(`/api/projects/${projectId}`);
  return res.data;
};

/**
 * Mengupdate metadata proyek
 * PUT /api/projects/{id}
 */
export const updateProject = async (projectId, projectData) => {
  const res = await request(`/api/projects/${projectId}`, {
    method: 'PUT',
    body: JSON.stringify(projectData)
  });
  return res.data;
};

/**
 * Menghapus proyek beserta seluruh data WBS (Cascade)
 * DELETE /api/projects/{id}
 */
export const deleteProject = async (projectId) => {
  return await request(`/api/projects/${projectId}`, {
    method: 'DELETE'
  });
};

// ==========================================
// 2. Modul Estimasi WBS & AHSP
// ==========================================

/**
 * Menyimpan full JSON hasil AI ke database
 * POST /api/projects/{id}/save-estimation
 * @param {string|number} projectId
 * @param {object} estimationData (format summary_metrics & sections)
 */
export const saveProjectEstimation = async (projectId, estimationData) => {
  const payload = transformAiResponseToSavePayload(estimationData);
  const res = await request(`/api/projects/${projectId}/save-estimation`, {
    method: 'POST',
    body: JSON.stringify(payload)
  });
  return res.data;
};

/**
 * Mengambil data WBS terakhir untuk render tabel UI
 * GET /api/projects/{id}/latest-estimation
 */
export const fetchLatestEstimation = async (projectId) => {
  const res = await request(`/api/projects/${projectId}/latest-estimation`);
  return res.data;
};

/**
 * Mengambil data estimasi berdasarkan ID run tertentu
 * GET /api/estimation-runs/{run_id}
 */
export const fetchEstimationRun = async (runId) => {
  const res = await request(`/api/estimation-runs/${runId}`);
  return res.data;
};

// ==========================================
// 3. Modul Item Pekerjaan WBS (/api/estimation-items)
// ==========================================

/**
 * Update volume, harga satuan, atau kode AHSP per baris
 * PUT /api/estimation-items/{db_id}
 * @param {string|number} itemDbId
 * @param {{ volume?: number, unit_price?: number, ahsp_code?: string, ahsp_status?: string, ahsp_name?: string, ahsp_unit?: string }} itemData
 */
export const updateEstimationItem = async (itemDbId, itemData) => {
  const res = await request(`/api/estimation-items/${itemDbId}`, {
    method: 'PUT',
    body: JSON.stringify(itemData)
  });
  return res.data;
};

/**
 * Hapus 1 baris item pekerjaan
 * DELETE /api/estimation-items/{db_id}
 */
export const deleteEstimationItem = async (itemDbId) => {
  return await request(`/api/estimation-items/${itemDbId}`, {
    method: 'DELETE'
  });
};

// ==========================================
// 4. Analisis Berkas DED (Upload & AI Takeoff)
// ==========================================

export const getPythonBaseUrl = () => {
  if (import.meta.env.VITE_PYTHON_API_URL) {
    return import.meta.env.VITE_PYTHON_API_URL.replace(/\/$/, '');
  }
  return typeof window !== 'undefined' && window.location.hostname
    ? `http://${window.location.hostname}:8200`
    : 'http://localhost:8200';
};

/**
 * Mengirim file DED ke backend untuk dianalisis oleh AI
 * POST /api/rab/analyze-image
 * Mendukung fallback otomatis ke direct Python API jika CI4 backend tidak merespon / offline.
 */
export const analyzeDED = async (projectName, clientName, file) => {
  const primaryUrl = import.meta.env.VITE_BACKEND_API_URL || `${getBackendBaseUrl()}/api/rab/analyze-image`;
  const pythonDirectUrl = `${getPythonBaseUrl()}/api/rab/analyze-image`;

  const createFormData = () => {
    const fd = new FormData();
    fd.append('name', projectName);
    fd.append('client', clientName);
    fd.append('ded_file', file);
    return fd;
  };

  let response;
  try {
    response = await fetch(primaryUrl, {
      method: 'POST',
      body: createFormData()
    });
  } catch (netErr) {
    console.warn(`Primary URL ${primaryUrl} failed (${netErr.message}). Mencoba direct Python API di ${pythonDirectUrl}...`);
    try {
      response = await fetch(pythonDirectUrl, {
        method: 'POST',
        body: createFormData()
      });
    } catch (fallbackErr) {
      throw new Error(`Tidak dapat terhubung ke server backend (${primaryUrl}) maupun AI engine (${pythonDirectUrl}): ${fallbackErr.message}`);
    }
  }

  if (!response.ok) {
    if (response.status === 404 || response.status === 502 || response.status === 503) {
      try {
        console.warn(`Backend returned ${response.status}. Mencoba direct Python API fallback...`);
        const fallbackResp = await fetch(pythonDirectUrl, {
          method: 'POST',
          body: createFormData()
        });
        if (fallbackResp.ok) {
          return await fallbackResp.json();
        }
      } catch (_e) {}
    }

    let errorMessage = `Server returned error ${response.status}`;
    try {
      const errorJson = await response.json();
      if (errorJson && errorJson.detail) {
        errorMessage = errorJson.detail;
      } else if (errorJson && errorJson.message) {
        errorMessage = errorJson.message;
      }
    } catch (_e) {
      if (response.statusText) {
        errorMessage = `Error ${response.status}: ${response.statusText}`;
      } else {
        errorMessage = `Error ${response.status}: Koneksi terputus atau server tidak merespon`;
      }
    }
    throw new Error(errorMessage);
  }

  const result = await response.json();
  return result;
};

/**
 * Mengirim prompt teks / konsep imajinasi rumah ke backend untuk dianalisis oleh AI
 * POST /api/rab/analyze-prompt
 * Mendukung fallback otomatis ke direct Python API jika CI4 backend tidak merespon / offline.
 */
export const analyzePrompt = async (projectName, clientName, promptText) => {
  const primaryUrl = `${getBackendBaseUrl()}/api/rab/analyze-prompt`;
  const pythonDirectUrl = `${getPythonBaseUrl()}/api/rab/analyze-prompt`;

  const payload = JSON.stringify({
    name: projectName,
    client: clientName,
    prompt: promptText
  });

  const sendRequest = async (url) => {
    return await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: payload
    });
  };

  let response;
  try {
    response = await sendRequest(primaryUrl);
  } catch (netErr) {
    console.warn(`Primary URL ${primaryUrl} failed (${netErr.message}). Mencoba direct Python API di ${pythonDirectUrl}...`);
    try {
      response = await sendRequest(pythonDirectUrl);
    } catch (fallbackErr) {
      throw new Error(`Tidak dapat terhubung ke server backend (${primaryUrl}) maupun AI engine (${pythonDirectUrl}): ${fallbackErr.message}`);
    }
  }

  if (!response.ok) {
    if (response.status === 404 || response.status === 502 || response.status === 503) {
      try {
        console.warn(`Backend returned ${response.status}. Mencoba direct Python API fallback...`);
        const fallbackResp = await sendRequest(pythonDirectUrl);
        if (fallbackResp.ok) {
          return await fallbackResp.json();
        }
      } catch (_e) {}
    }

    let errorMessage = `Server returned error ${response.status}`;
    try {
      const errorJson = await response.json();
      if (errorJson && errorJson.detail) {
        errorMessage = errorJson.detail;
      } else if (errorJson && errorJson.message) {
        errorMessage = errorJson.message;
      }
    } catch (_e) {
      if (response.statusText) {
        errorMessage = `Error ${response.status}: ${response.statusText}`;
      } else {
        errorMessage = `Error ${response.status}: Koneksi terputus atau server tidak merespon`;
      }
    }
    throw new Error(errorMessage);
  }

  const result = await response.json();
  return result;
};

// ==========================================
// 5. Transformasi & Adapter Format Data
// ==========================================

/**
 * Mengubah response hierarki backend (sections -> items)
 * menjadi flat rows array yang kompatibel dengan tabel WBS di Anggaran.jsx
 */
export const transformBackendToFlatRows = (backendData) => {
  if (!backendData || !Array.isArray(backendData.sections)) {
    return [];
  }

  const rows = [];

  backendData.sections.forEach((sec, sIdx) => {
    const sectionCode = sec.code || String.fromCharCode(65 + sIdx); // A, B, C...
    const sectionId = sec.id || `sec-${sectionCode}`;

    // Header Section Row
    rows.push({
      id: sectionId,
      db_id: sec.db_id,
      type: 'section',
      code: sectionCode,
      name: sec.name || `BAGIAN ${sectionCode}`
    });

    if (Array.isArray(sec.items)) {
      sec.items.forEach((item, iIdx) => {
        const itemNumber = item.no || (iIdx + 1);
        const itemCode = item.code || `${sectionCode}.${itemNumber}`;
        const itemId = item.id || `item-${sectionCode}-${itemNumber}`;

        const unitPrice = parseFloat(item.unit_price) || 0;
        const volume = parseFloat(item.volume) || 0;

        // Normalisasi kandidat AHSP
        const candidates = item.candidates || (item.ahsp_mapping && item.ahsp_mapping.candidates) || [];

        const itemAhspStatus = item.ahsp_status || (item.ahsp_mapping?.ahsp_status) || 'mapped_high';
        const itemAhspUnit = item.ahsp_unit || (item.ahsp_mapping?.ahsp_unit) || '';
        // Aturan satuan: Jika sudah terpetakan ke AHSP dan satuan AHSP tersedia, gunakan satuan AHSP
        const effectiveUnit = (itemAhspStatus !== 'unmapped' && itemAhspUnit) ? itemAhspUnit : (item.unit || 'm2');

        rows.push({
          id: itemId,
          db_id: item.db_id,
          type: 'item',
          sectionCode: sectionCode,
          no: itemNumber,
          code: itemCode,
          name: item.name || '',
          volume: volume,
          unit: effectiveUnit,
          unitPrice: unitPrice,
          confidence: item.confidence || 'high',
          warning_note: item.warning_note || null,
          ahsp_code: item.ahsp_code || (item.ahsp_mapping?.ahsp_code) || '',
          ahsp_name: item.ahsp_name || (item.ahsp_mapping?.ahsp_name) || item.name,
          ahsp_unit: itemAhspUnit || effectiveUnit,
          ahsp_status: itemAhspStatus,
          ahsp_score: typeof item.ahsp_score === 'number' ? item.ahsp_score : (item.ahsp_mapping?.ahsp_score ?? 1.0),
          ahsp_candidates: candidates
        });
      });
    }
  });

  return rows;
};

/**
 * Mengubah raw output dari AI / format frontend flat rows
 * menjadi payload JSON standar untuk POST /api/projects/{id}/save-estimation
 */
export const transformAiResponseToSavePayload = (aiResult) => {
  // Jika sudah dalam format payload baku (ada sections array)
  if (aiResult && Array.isArray(aiResult.sections)) {
    return {
      summary_metrics: aiResult.summary_metrics || {
        total_items: aiResult.sections.reduce((acc, s) => acc + (s.items?.length || 0), 0),
        mapped_high: 0,
        mapped_medium: 0,
        unmapped: 0,
        high_ratio: 100.0
      },
      sections: aiResult.sections.map((sec, sIdx) => ({
        id: sec.id || `sec-${sec.code || sIdx + 1}`,
        code: sec.code || String.fromCharCode(65 + sIdx),
        name: sec.name || 'PEKERJAAN',
        items: (sec.items || []).map((item, iIdx) => ({
          id: item.id || `item-${sec.code || 'A'}-${iIdx + 1}`,
          no: item.no || (iIdx + 1),
          code: item.code || `${sec.code || 'A'}.${iIdx + 1}`,
          name: item.name || item.uraian || '',
          volume: parseFloat(item.volume ?? item.volume_est ?? 1),
          unit: item.unit || item.satuan || 'm2',
          confidence: item.confidence || 'high',
          warning_note: item.warning_note || null,
          unit_price: parseFloat(item.unit_price ?? item.unitPrice ?? 0),
          ahsp_mapping: item.ahsp_mapping || {
            ahsp_code: item.ahsp_code || item.kode_ahsp || '',
            ahsp_name: item.ahsp_name || item.name || item.uraian || '',
            ahsp_unit: item.ahsp_unit || item.unit || item.satuan || 'm2',
            ahsp_score: item.ahsp_score ?? 1.0,
            ahsp_status: item.ahsp_status || 'mapped_high',
            candidates: item.ahsp_candidates || item.candidates || []
          }
        }))
      }))
    };
  }

  // Jika formatnya flat array `items` (seperti dari mapToFrontendFormat atau Python Takeoff)
  const itemsList = Array.isArray(aiResult) ? aiResult : (aiResult?.items || []);
  const sectionsMap = {};

  let totalItems = 0;
  let mappedHigh = 0;
  let mappedMedium = 0;
  let unmapped = 0;

  let currentSectionCode = 'A';
  let currentSectionName = 'PEKERJAAN PERSIAPAN';

  itemsList.forEach((row, idx) => {
    if (row.type === 'section') {
      currentSectionCode = row.code || String.fromCharCode(65 + Object.keys(sectionsMap).length);
      currentSectionName = row.name || `PEKERJAAN ${currentSectionCode}`;
      if (!sectionsMap[currentSectionCode]) {
        sectionsMap[currentSectionCode] = {
          id: row.id || `sec-${currentSectionCode}`,
          code: currentSectionCode,
          name: currentSectionName,
          items: []
        };
      }
      return;
    }

    const secCode = row.sectionCode || currentSectionCode;
    if (!sectionsMap[secCode]) {
      sectionsMap[secCode] = {
        id: `sec-${secCode}`,
        code: secCode,
        name: currentSectionName,
        items: []
      };
    }

    totalItems++;
    const status = row.ahsp_status || (row.ahsp_mapping?.ahsp_status) || 'mapped_high';
    if (status === 'mapped_high') mappedHigh++;
    else if (status === 'mapped_medium') mappedMedium++;
    else unmapped++;

    const itemObj = {
      id: row.id || `item-${secCode}-${idx + 1}`,
      no: row.no || sectionsMap[secCode].items.length + 1,
      code: row.code || `${secCode}.${sectionsMap[secCode].items.length + 1}`,
      name: row.name || row.uraian || '',
      volume: parseFloat(row.volume ?? row.volume_est ?? 1),
      unit: row.unit || row.satuan || 'm2',
      confidence: row.confidence || 'high',
      warning_note: row.warning_note || null,
      unit_price: parseFloat(row.unitPrice ?? row.unit_price ?? 0),
      ahsp_mapping: row.ahsp_mapping || {
        ahsp_code: row.ahsp_code || row.kode_ahsp || '',
        ahsp_name: row.ahsp_name || row.name || row.uraian || '',
        ahsp_unit: row.ahsp_unit || row.unit || row.satuan || 'm2',
        ahsp_score: row.ahsp_score ?? 1.0,
        ahsp_status: status,
        candidates: row.ahsp_candidates || row.candidates || []
      }
    };

    sectionsMap[secCode].items.push(itemObj);
  });

  return {
    summary_metrics: {
      total_items: totalItems,
      mapped_high: mappedHigh,
      mapped_medium: mappedMedium,
      unmapped: unmapped,
      high_ratio: totalItems > 0 ? Number(((mappedHigh / totalItems) * 100).toFixed(2)) : 100.0
    },
    sections: Object.values(sectionsMap)
  };
};

/**
 * Backward compatibility helper for legacy code
 */
export const mapToFrontendFormat = (projectName, clientName, llmData) => {
  if (llmData && llmData.project && Array.isArray(llmData.items) && llmData.items.length > 0 && llmData.items[0].type) {
    return {
      project: {
        title: llmData.project.title || projectName,
        client: llmData.project.client || clientName,
        budget: 0,
        status: llmData.project.status || 'Perencanaan'
      },
      anggaran: llmData.items
    };
  }

  // Jika response memiliki sections
  if (llmData && Array.isArray(llmData.sections)) {
    return {
      project: {
        title: projectName,
        client: clientName,
        budget: 0,
        status: 'Perencanaan'
      },
      anggaran: transformBackendToFlatRows(llmData)
    };
  }

  const payload = transformAiResponseToSavePayload(llmData);
  return {
    project: {
      title: projectName,
      client: clientName,
      budget: 0,
      status: 'Perencanaan'
    },
    anggaran: transformBackendToFlatRows(payload)
  };
};
