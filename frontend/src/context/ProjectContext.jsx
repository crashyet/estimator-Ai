import React, { createContext, useContext, useState, useCallback, useRef } from 'react';
import {
  fetchProjectById,
  fetchLatestEstimation,
  fetchEstimationRun,
  transformBackendToFlatRows
} from '../services/api';

// Default standard Indonesian construction WBS categories
export const DEFAULT_SECTIONS = [
  { id: 'sec-A', type: 'section', code: 'A', name: 'PEKERJAAN PERSIAPAN' },
  { id: 'sec-B', type: 'section', code: 'B', name: 'PEKERJAAN TANAH DAN PONDASI' },
  { id: 'sec-C', type: 'section', code: 'C', name: 'PEKERJAAN STRUKTUR BETON' },
  { id: 'sec-D', type: 'section', code: 'D', name: 'PEKERJAAN ARSITEKTUR (DINDING, LANTAI, PLAFOND)' },
  { id: 'sec-E', type: 'section', code: 'E', name: 'PEKERJAAN KUSEN, PINTU, DAN JENDELA' },
  { id: 'sec-F', type: 'section', code: 'F', name: 'PEKERJAAN ATAP' },
  { id: 'sec-G', type: 'section', code: 'G', name: 'PEKERJAAN MEP & UTILITAS' },
];

const ProjectContext = createContext(null);

export const ProjectProvider = ({ children }) => {
  const [activeProjectId, setActiveProjectId] = useState(null);
  const [activeRunId, setActiveRunId] = useState(null);

  const [projectDetail, setProjectDetail] = useState({
    id: null,
    uuid: null,
    title: '',
    client: '',
    status: '',
    location: '',
    estimation_runs: []
  });

  const [rows, setRows] = useState(DEFAULT_SECTIONS);
  const [estimationRun, setEstimationRun] = useState(null);
  const [isLoadingWbs, setIsLoadingWbs] = useState(false);

  // References to keep synchronous track and prevent race conditions
  const loadedProjectIdRef = useRef(null);
  const loadedRunIdRef = useRef(null);

  // Apply any pending row update stored in sessionStorage (e.g., from Pemetaan AHSP)
  const applyPendingRowUpdate = useCallback(() => {
    try {
      const pendingUpdate = sessionStorage.getItem('estimator_last_updated_row');
      if (pendingUpdate) {
        const parsed = JSON.parse(pendingUpdate);
        setRows(prevRows => prevRows.map(r => {
          if (
            (parsed.db_id && r.db_id === parsed.db_id) ||
            (parsed.id && r.id === parsed.id)
          ) {
            return {
              ...r,
              ahsp_code: parsed.ahsp_code,
              ahsp_name: parsed.ahsp_name,
              ahsp_unit: parsed.ahsp_unit,
              unit: parsed.unit,
              ahsp_status: parsed.ahsp_status || 'mapped_high',
              ahsp_score: parsed.ahsp_score ?? r.ahsp_score
            };
          }
          return r;
        }));
        sessionStorage.removeItem('estimator_last_updated_row');
      }
    } catch (_e) { }
  }, []);

  // Main loader: Checks if the project is already loaded in memory
  const loadProjectAndEstimation = useCallback(async (targetProjectId, queryRunId = null, forceRefresh = false) => {
    if (!targetProjectId || targetProjectId === 'undefined' || targetProjectId === 'null') {
      return { success: false, notFound: true };
    }

    // Check if data is already in memory for this project and run
    const isSameProject =
      loadedProjectIdRef.current &&
      (String(loadedProjectIdRef.current) === String(targetProjectId) ||
        String(projectDetail.id) === String(targetProjectId) ||
        String(projectDetail.uuid) === String(targetProjectId));

    const isSameRun = !queryRunId || String(loadedRunIdRef.current) === String(queryRunId);

    if (!forceRefresh && isSameProject && isSameRun && rows.length > 0) {
      // Instant cache hit: 0ms, no network calls, no spinner!
      // Still apply any pending updates from Pemetaan AHSP if navigated back
      applyPendingRowUpdate();
      return { success: true, cached: true };
    }

    // Otherwise, fetch from backend
    setIsLoadingWbs(true);

    try {
      localStorage.removeItem(`estimator_uploaded_rows_${targetProjectId}`);
    } catch (_e) { }

    try {
      // 1. Fetch Project Metadata
      let fetchedProject = null;
      try {
        const pData = await fetchProjectById(targetProjectId);
        if (pData) {
          fetchedProject = pData;
          setProjectDetail({
            id: pData.id,
            uuid: pData.uuid,
            title: pData.title || '',
            client: pData.client || '',
            status: pData.status || '',
            location: pData.location || '',
            estimation_runs: pData.estimation_runs || []
          });
        } else {
          setIsLoadingWbs(false);
          return { success: false, notFound: true };
        }
      } catch (err) {
        console.warn("Could not fetch project details from backend:", err.message);
        setIsLoadingWbs(false);
        const isNotFound = err.status === 404 || err.message?.includes('404') || err.message?.toLowerCase().includes('not found');
        return { success: false, notFound: isNotFound, error: err.message };
      }

      // 2. Fetch Estimation Run
      let runData = null;
      if (queryRunId) {
        try {
          runData = await fetchEstimationRun(queryRunId);
        } catch (err) {
          console.warn("Could not fetch run from query run_id:", err.message);
        }
      }

      if (!runData) {
        const estData = await fetchLatestEstimation(targetProjectId);
        if (estData && estData.run_id) {
          try {
            const fetchedRun = await fetchEstimationRun(estData.run_id);
            runData = fetchedRun || estData;
          } catch (runErr) {
            console.warn("Could not fetch run via fetchEstimationRun, using latest estimation data:", runErr.message);
            runData = estData;
          }
        } else {
          runData = estData;
        }
      }

      if (runData) {
        setEstimationRun(runData);
        if (Array.isArray(runData.sections) && runData.sections.length > 0) {
          let flatRows = transformBackendToFlatRows(runData);

          // Apply pending update if any
          try {
            const pendingUpdate = sessionStorage.getItem('estimator_last_updated_row');
            if (pendingUpdate) {
              const parsed = JSON.parse(pendingUpdate);
              flatRows = flatRows.map(r => {
                if (
                  (parsed.db_id && r.db_id === parsed.db_id) ||
                  (parsed.id && r.id === parsed.id)
                ) {
                  return {
                    ...r,
                    ahsp_code: parsed.ahsp_code,
                    ahsp_name: parsed.ahsp_name,
                    ahsp_unit: parsed.ahsp_unit,
                    unit: parsed.unit,
                    ahsp_status: parsed.ahsp_status || 'mapped_high',
                    ahsp_score: parsed.ahsp_score ?? r.ahsp_score
                  };
                }
                return r;
              });
              sessionStorage.removeItem('estimator_last_updated_row');
            }
          } catch (_e) { }

          setRows(flatRows);
        } else {
          setRows(DEFAULT_SECTIONS);
        }
      } else {
        setEstimationRun(null);
        setRows(DEFAULT_SECTIONS);
      }

      // Record currently active project and run in refs & state
      const resolvedProjId = fetchedProject?.uuid || fetchedProject?.id || targetProjectId;
      loadedProjectIdRef.current = resolvedProjId;
      loadedRunIdRef.current = queryRunId || runData?.run_id || null;
      setActiveProjectId(resolvedProjId);
      setActiveRunId(queryRunId || runData?.run_id || null);

      return { success: true, project: fetchedProject };
    } catch (err) {
      console.warn("Could not load project estimation:", err.message);
      setEstimationRun(null);
      setRows(DEFAULT_SECTIONS);
      return { success: false, error: err.message };
    } finally {
      setIsLoadingWbs(false);
    }
  }, [projectDetail.id, projectDetail.uuid, rows.length, applyPendingRowUpdate]);

  // Helper to update a row in-place
  const updateRow = useCallback((rowIdentifier, updatedFields) => {
    setRows(prevRows => prevRows.map(r => {
      if (
        (r.db_id && r.db_id === rowIdentifier) ||
        (r.id && r.id === rowIdentifier)
      ) {
        return { ...r, ...updatedFields };
      }
      return r;
    }));
  }, []);

  // Helper to reset project data to default
  const resetProjectData = useCallback(() => {
    sessionStorage.clear();
    setRows(DEFAULT_SECTIONS);
    setEstimationRun(null);
  }, []);

  const value = {
    activeProjectId,
    activeRunId,
    projectDetail,
    setProjectDetail,
    rows,
    setRows,
    updateRow,
    estimationRun,
    setEstimationRun,
    estimationRuns: projectDetail.estimation_runs || [],
    isLoadingWbs,
    loadProjectAndEstimation,
    applyPendingRowUpdate,
    resetProjectData
  };

  return (
    <ProjectContext.Provider value={value}>
      {children}
    </ProjectContext.Provider>
  );
};

export const useProject = () => {
  const context = useContext(ProjectContext);
  if (!context) {
    throw new Error('useProject must be used within a ProjectProvider');
  }
  return context;
};
