<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from '@/helpers/pms_axios';
import { useUsersStore } from '@/stores/user';
import { showAlert, parseSmart, parseList, calculateAverageRating } from '@/helpers/essential';
import { depts, branchs } from '@/data/masterdata';
import melcomLogo from '@/assets/img/melcom_logo.png';
import PrintableHardCopyDossier from '@/components/pms/PrintableHardCopyDossier.vue';

const userstore = useUsersStore();

const isDark = ref(document.body.classList.contains('dark-mode'));
const _darkObserver = new MutationObserver(() => { isDark.value = document.body.classList.contains('dark-mode'); });
_darkObserver.observe(document.body, { attributes: true, attributeFilter: ['class'] });

const submissions = ref([]);
const loading = ref(true);
watch(loading, (val) => userstore?.setIsLoading?.(val), { immediate: true });
const selectedAppraisal = ref(null);
const showActionModal = ref(false);
const actionData = ref({ status: 'approved', hr_comments: '', overall_rating: 4.0 });
const viewMode = ref('list');

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch (e) {
        return dateString;
    }
};

const getRecordId = (goal) => {
    if (!goal) return 'N/A';
    const dept = goal.user?.department || goal.department || 'NA';
    const code = goal.user?.employee_code || goal.employee_code || goal.id || '---';
    const abbr = dept.includes(' ')
        ? dept.split(' ').map(w => w[0]).join('').toUpperCase()
        : (dept.length >= 3 ? dept.substring(0, 3).toUpperCase() : dept.toUpperCase());
    return `${abbr}-${code}`;
};

// ── Filters ──
const filterStatus = ref('all');
const filterDepartment = ref('');
const filterLocation = ref('');
const searchQuery = ref('');
const employeeCodeSearch = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const showAdvancedFilters = ref(false);
const openDropdown = ref(''); // tracks which filter dropdown is open

// ── Sorting ──
const sortField = ref('submitted_at');
const sortOrder = ref('desc');
const setSorting = (field) => {
    if (sortField.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortOrder.value = 'desc';
    }
};
const sortIcon = (field) => {
    if (sortField.value !== field) return 'pi pi-sort-alt';
    return sortOrder.value === 'asc' ? 'pi pi-sort-amount-up-alt' : 'pi pi-sort-amount-down';
};

// ── Pagination ──
const currentPage = ref(1);
const rowsPerPage = ref(15);

const getCompositeRating = (appraisals) => {
    if (!appraisals.length) return '0.0';
    const total = appraisals.reduce((sum, s) => sum + parseFloat(calculateAverageRating(s)), 0);
    return (total / appraisals.length).toFixed(1);
};

const getCompositeStatus = (appraisals) => {
    const isAllApproved = appraisals.every(s => s.status === 'approved');
    const isAnyRejected = appraisals.some(s => s.status === 'rejected');
    if (isAnyRejected) return 'Rejected';
    return isAllApproved ? 'Reviewed' : 'Submitted';
};

const smartLabels = [
    { key: 'specific', label: 'Specific', short: 'S', color: 'bg-[#2E7D32] text-white' },
    { key: 'measurable', label: 'Measurable', short: 'M', color: 'bg-[#1565C0] text-white' },
    { key: 'attainable', label: 'Attainable', short: 'A', color: 'bg-[#7B1FA2] text-white' },
    { key: 'relevant', label: 'Relevant', short: 'R', color: 'bg-[#C62828] text-white' },
    { key: 'time_bound', label: 'Time-bound', short: 'T', color: 'bg-[#1A237E] text-white' }
];

const getTimeProgress = (goal) => {
    if (!goal || !goal.completion_date) return 0;
    const start = new Date(goal.submitted_at || goal.created_at || `${currentYear.value}-01-01`);
    const end = new Date(goal.completion_date);
    const today = new Date();
    if (today >= end) return 100;
    if (today <= start) return 0;
    return Math.min(100, Math.max(0, Math.round(((today - start) / (end - start)) * 100)));
};

const getSmartClass = (labelShort, appraisal) => {
    if (!appraisal) return 'bg-slate-50 text-slate-300';
    const smart = typeof appraisal.smart_criteria === 'string' ? JSON.parse(appraisal.smart_criteria) : (appraisal.smart_criteria || {});
    const item = smartLabels.find(l => l.short === labelShort);
    if (!item) return 'bg-slate-50 text-slate-300';
    return smart[item.key] ? `${item.color} shadow-sm font-black` : 'bg-slate-50 text-slate-200';
};

const currentYear = ref(new Date().getFullYear());

const stats = computed(() => {
    const all = submissions.value;
    const pending = all.filter(s => s.status === 'pending' || s.status === 'submitted');
    const approved = all.filter(s => s.status === 'approved');
    const rejected = all.filter(s => s.status === 'rejected');
    const completed = all.filter(s => s.status === 'completed' || s.status === 'review_completed');
    const rated = all.filter(s => parseFloat(calculateAverageRating(s)) > 0);
    const avgRating = rated.length > 0
        ? (rated.reduce((sum, s) => sum + parseFloat(calculateAverageRating(s)), 0) / rated.length).toFixed(1)
        : '0.0';
    return {
        total: all.length,
        pending: pending.length,
        approved: approved.length,
        rejected: rejected.length,
        completed: completed.length,
        rated: rated.length,
        avgRating
    };
});

// ── Filtered + Sorted flat list (replaces grouped approach for tabular view) ──
const filteredSubmissions = computed(() => {
    let data = [...submissions.value];

    // Search filter (name, title, manager, employee code)
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        data = data.filter(s => {
            const name = (s.candidate_name || s.user?.name || '').toLowerCase();
            const title = (s.title || '').toLowerCase();
            const mgr = (s.manager_name || '').toLowerCase();
            const empCode = (s.user?.employee_code || s.employee_code || '').toLowerCase();
            return name.includes(q) || title.includes(q) || mgr.includes(q) || empCode.includes(q);
        });
    }

    // Employee code filter (kept for backward compat)
    if (employeeCodeSearch.value) {
        const code = employeeCodeSearch.value.toLowerCase();
        data = data.filter(s => {
            const empCode = (s.user?.employee_code || s.employee_code || '').toLowerCase();
            return empCode.includes(code);
        });
    }

    // Status filter
    if (filterStatus.value !== 'all') {
        data = data.filter(s => s.status === filterStatus.value);
    }

    // Department filter
    if (filterDepartment.value) {
        data = data.filter(s => (s.user?.department || s.department) === filterDepartment.value);
    }

    // Location filter
    if (filterLocation.value) {
        data = data.filter(s => (s.user?.location || s.location) === filterLocation.value);
    }

    // Date range filter
    if (dateFrom.value) {
        const from = new Date(dateFrom.value);
        data = data.filter(s => new Date(s.submitted_at || s.created_at) >= from);
    }
    if (dateTo.value) {
        const to = new Date(dateTo.value);
        to.setHours(23, 59, 59);
        data = data.filter(s => new Date(s.submitted_at || s.created_at) <= to);
    }

    // Sorting
    data.sort((a, b) => {
        let valA, valB;
        switch (sortField.value) {
            case 'name':
                valA = (a.candidate_name || a.user?.name || '').toLowerCase();
                valB = (b.candidate_name || b.user?.name || '').toLowerCase();
                break;
            case 'employee_code':
                valA = (a.user?.employee_code || a.employee_code || '').toLowerCase();
                valB = (b.user?.employee_code || b.employee_code || '').toLowerCase();
                break;
            case 'department':
                valA = (a.user?.department || a.department || '').toLowerCase();
                valB = (b.user?.department || b.department || '').toLowerCase();
                break;
            case 'rating':
                valA = parseFloat(calculateAverageRating(a)) || 0;
                valB = parseFloat(calculateAverageRating(b)) || 0;
                break;
            case 'status':
                valA = (a.status || '').toLowerCase();
                valB = (b.status || '').toLowerCase();
                break;
            case 'submitted_at':
            default:
                valA = new Date(a.submitted_at || a.created_at || 0).getTime();
                valB = new Date(b.submitted_at || b.created_at || 0).getTime();
                break;
        }
        if (valA < valB) return sortOrder.value === 'asc' ? -1 : 1;
        if (valA > valB) return sortOrder.value === 'asc' ? 1 : -1;
        return 0;
    });

    return data;
});

// Paginated subset
const paginatedSubmissions = computed(() => {
    const start = (currentPage.value - 1) * rowsPerPage.value;
    return filteredSubmissions.value.slice(start, start + rowsPerPage.value);
});
const totalPages = computed(() => Math.ceil(filteredSubmissions.value.length / rowsPerPage.value) || 1);

// Reset page when filters change
watch([searchQuery, employeeCodeSearch, filterStatus, filterDepartment, filterLocation, dateFrom, dateTo], () => {
    currentPage.value = 1;
});

// Active filter count
const activeFilterCount = computed(() => {
    let count = 0;
    if (searchQuery.value) count++;
    if (employeeCodeSearch.value) count++;
    if (filterStatus.value !== 'all') count++;
    if (filterDepartment.value) count++;
    if (filterLocation.value) count++;
    if (dateFrom.value) count++;
    if (dateTo.value) count++;
    return count;
});

const clearAllFilters = () => {
    searchQuery.value = '';
    employeeCodeSearch.value = '';
    filterStatus.value = 'all';
    filterDepartment.value = '';
    filterLocation.value = '';
    dateFrom.value = '';
    dateTo.value = '';
};

const fetchSubmissions = async () => {
    loading.value = true;
    userstore?.setIsLoading?.(true);
    try {
        const response = await axios.get('pms/appraisals/all', {
            params: { year: currentYear.value }
        });
        if (response.data.status === 'success') {
            submissions.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching submissions:', error);
    } finally {
        loading.value = false;
        userstore?.setIsLoading?.(false);
    }
};

const openActionModal = (appraisal) => {
    selectedAppraisal.value = appraisal;
    actionData.value = {
        status: appraisal.status === 'pending' ? 'approved' : appraisal.status,
        hr_comments: appraisal.hr_comments || '',
        overall_rating: appraisal.overall_rating || 4.0
    };
    showActionModal.value = true;
};

const downloadCSV = () => {
    if (!selectedAppraisal.value) return;
    const s = selectedAppraisal.value;
    const ad = parseSmart(s.appraisal_data || '{}') || {};
    const comps = ad.competencies || [];

    const headers = [
        'Employee Code', 'Employee Name', 'Department', 'Location', 'Job Title', 'Line Manager',
        'Year', 'Status', 'Goal Title', 'Category', 'Target %',
        'Description/Objectives', 'Purposes', 'Challenges',
        'SMART-Specific', 'SMART-Measurable', 'SMART-Attainable', 'SMART-Relevant', 'SMART-TimeBound'
    ];
    [1, 2, 3, 4].forEach(q => {
        headers.push(`Q${q} Start Date`, `Q${q} End Date`, `Q${q} Target Measures`, `Q${q} Evidence`);
    });
    comps.forEach(c => {
        headers.push(`${c.title} (Weight)`, `${c.title} (Self)`, `${c.title} (Mgr)`);
    });
    headers.push(
        'Overall Manager Rating', 'Performance Rating', 'Potential Rating',
        'What Impressed Most', 'What Impressed Least', 'General Comments',
        'HOD Comments', 'Director Remarks',
        'Employee Signature', 'Manager Signature', 'Signature Date',
        'HOD Signature', 'HOD Signature Date', 'Director Signature', 'Director Signature Date'
    );
    headers.push('Achievements vs Targets', 'Initiatives Taken', 'Next Steps', 'Areas for Improvement', 'Manager Observations');

    const smart = typeof s.smart_criteria === 'string' ? JSON.parse(s.smart_criteria || '{}') : (s.smart_criteria || {});
    const row = [
        s.user?.employee_code || s.employee_code || 'N/A',
        s.candidate_name || s.user?.name || 'N/A',
        s.user?.department || s.department || 'N/A',
        s.user?.location || s.location || 'N/A',
        s.user?.job_title || 'N/A',
        s.manager_name || 'N/A',
        s.year, s.status,
        s.title || 'N/A', s.category || 'General', `${s.target || 100}%`,
        Array.isArray(parseList(s.description)) ? parseList(s.description).join('; ') : (s.description || ''),
        Array.isArray(parseList(s.purposes)) ? parseList(s.purposes).join('; ') : (s.purposes || ''),
        Array.isArray(parseList(s.challenges)) ? parseList(s.challenges).join('; ') : (s.challenges || ''),
        smart.specific ? 'Yes' : 'No', smart.measurable ? 'Yes' : 'No',
        smart.attainable ? 'Yes' : 'No', smart.relevant ? 'Yes' : 'No',
        smart.time_bound ? 'Yes' : 'No'
    ];
    const quarters = parseList(s.quarterly_tracking);
    [0, 1, 2, 3].forEach(idx => {
        const q = quarters[idx] || {};
        row.push(q.start_date || 'N/A', q.end_date || 'N/A',
            Array.isArray(parseList(q.target_measures)) ? parseList(q.target_measures).join('; ') : (q.target_measures || 'N/A'),
            q.evidence || '');
    });
    comps.forEach(c => {
        row.push(`${c.weight}%`, c.selfRating || 0, c.managerRating || 0);
    });
    row.push(
        calculateAverageRating(s) || 'N/A', ad.performanceRating || 'N/A', ad.potentialRating || 'N/A',
        ad.impressedMost || '', ad.impressedLeast || '', ad.comments || '',
        ad.hod_comments || '', ad.director_remarks || '',
        ad.candidate_signature_name || '', ad.manager_signature_name || '', ad.signature_date || '',
        ad.hod_signature_name || '', ad.hod_signature_date || '', ad.director_signature_name || '', ad.director_signature_date || ''
    );
    const rs = ad.review_summary || {};
    const ach = rs.A ? (Array.isArray(rs.A) ? rs.A.map(a => `${a.achievement || ''} -> ${a.target || ''}`).join('; ') : rs.A) : '';
    row.push(ach);
    row.push(rs.B ? (Array.isArray(rs.B) ? rs.B.join('; ') : rs.B) : '');
    row.push(rs.C ? (Array.isArray(rs.C) ? rs.C.join('; ') : rs.C) : '');
    row.push(rs.D ? (Array.isArray(rs.D) ? rs.D.join('; ') : rs.D) : '');
    row.push(rs.E ? (Array.isArray(rs.E) ? rs.E.join('; ') : rs.E) : '');

    const csvContent = "﻿" + headers.join(",") + "\n" + row.map(v => `"${String(v).replace(/"/g, '""')}"`).join(",");
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", `${s.candidate_name || s.user?.name || 'employee'}_pms_${s.year}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const downloadSinglePDF = (appraisal) => {
    selectedAppraisal.value = appraisal;
    showActionModal.value = true;
    viewMode.value = 'protocol';
    setTimeout(() => {
        const res = downloadPDF();
        if (res && typeof res.then === 'function') {
            res.then(() => { viewMode.value = 'list'; showActionModal.value = false; }).catch(() => { viewMode.value = 'list'; showActionModal.value = false; });
        } else {
            setTimeout(() => { viewMode.value = 'list'; showActionModal.value = false; }, 3000);
        }
    }, 800);
};

const exportSubmissionsCSV = () => {
    const data = filteredSubmissions.value;
    if (!data.length) {
        showAlert('Info', 'No data to export with current filters.', 'info');
        return;
    }
    const firstAppraisal = parseSmart(data[0].appraisal_data || '{}');
    const comps = firstAppraisal?.competencies || [];

    const headers = [
        'Employee Code', 'Employee Name', 'Department', 'Location', 'Line Manager',
        'Year', 'Status', 'Overall Rating', 'Submitted At',
        'Goal Title', 'Goal Description', 'Goal Purposes'
    ];
    comps.forEach(c => {
        headers.push(`${c.title} (Self)`, `${c.title} (Mgr)`);
    });
    headers.push('Performance Rating', 'Potential Rating', 'What Impressed Most', 'What Impressed Least', 'General Comments');
    headers.push('Review: Achievements', 'Review: Initiatives', 'Review: Next Steps', 'Review: Improvements', 'Review: Manager Observations');
    headers.push('HOD Comments', 'Director Remarks',
        'Employee Signature', 'Manager Signature', 'Signature Date',
        'HOD Signature', 'HOD Signature Date', 'Director Signature', 'Director Signature Date');
    [1, 2, 3, 4].forEach(q => {
        headers.push(`Q${q} Start Date`, `Q${q} End Date`, `Q${q} Target Measures`, `Q${q} Evidence`);
    });

    const rows = data.map(s => {
        const ad = parseSmart(s.appraisal_data || '{}');
        const row = [
            s.user?.employee_code || s.employee_code || s.user_id || 'N/A',
            s.user?.name || 'N/A',
            s.user?.department || s.department || 'N/A',
            s.user?.location || 'N/A',
            s.manager_name || 'Administrator',
            s.year, s.status, calculateAverageRating(s),
            s.submitted_at || s.created_at || 'N/A',
            s.title || 'N/A',
            Array.isArray(parseList(s.description)) ? parseList(s.description).join('; ') : (s.description || 'N/A'),
            Array.isArray(parseList(s.purposes)) ? parseList(s.purposes).join('; ') : (s.purposes || 'N/A')
        ];
        const competencyMap = ad?.competencies || [];
        comps.forEach((_, idx) => { const c = competencyMap[idx] || {}; row.push(c.selfRating || 0, c.managerRating || 0); });
        row.push(ad?.performanceRating || 'N/A', ad?.potentialRating || 'N/A',
            ad?.impressedMost || '', ad?.impressedLeast || '', ad?.comments || '');
        const rs = ad?.review_summary || {};
        const ach = rs.A ? (Array.isArray(rs.A) ? rs.A.map(a => `${a.achievement || ''} -> ${a.target || ''}`).join('; ') : rs.A) : '';
        row.push(ach);
        row.push(rs.B ? (Array.isArray(rs.B) ? rs.B.join('; ') : rs.B) : '');
        row.push(rs.C ? (Array.isArray(rs.C) ? rs.C.join('; ') : rs.C) : '');
        row.push(rs.D ? (Array.isArray(rs.D) ? rs.D.join('; ') : rs.D) : '');
        row.push(rs.E ? (Array.isArray(rs.E) ? rs.E.join('; ') : rs.E) : '');
        row.push(ad?.hod_comments || '', ad?.director_remarks || '',
            ad?.candidate_signature_name || '', ad?.manager_signature_name || '', ad?.signature_date || '',
            ad?.hod_signature_name || '', ad?.hod_signature_date || '', ad?.director_signature_name || '', ad?.director_signature_date || '');
        const quarters = parseList(s.quarterly_tracking);
        [0, 1, 2, 3].forEach(idx => {
            const q = quarters[idx] || {};
            const measures = Array.isArray(parseList(q.target_measures)) ? parseList(q.target_measures).join('; ') : (q.target_measures || 'N/A');
            row.push(q.start_date || 'N/A', q.end_date || 'N/A', measures, q.evidence || '');
        });
        return row;
    });

    let csvContent = "﻿" + [headers, ...rows].map(e => e.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(",")).join("\n");
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", `pms_comprehensive_export_${currentYear.value}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const downloadPDF = () => {
    let element = document.getElementById('protocol-report');
    if (!element) return;

    const runDownload = () => {
        const opt = {
          margin:       0,
          filename:     `PMS_Appraisal_${selectedAppraisal.value.candidate_name || selectedAppraisal.value.user?.name || selectedAppraisal.value.employee_code || selectedAppraisal.value.id}_${selectedAppraisal.value.year || 'Record'}.pdf`,
          image:        { type: 'jpeg', quality: 0.98 },
          html2canvas:  { scale: 2, useCORS: true, logging: false },
          jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
          pagebreak:    { mode: ['css', 'legacy'] }
        };
        return window.html2pdf().from(element).set(opt).save();
    };

    if (window.html2pdf) {
        return runDownload();
    } else {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
        script.onload = runDownload;
        document.head.appendChild(script);
    }
};

onMounted(() => {
    fetchSubmissions();
});

// --- Bulk Actions ---
const selectedIds = ref([]);
const selectAll = ref(false);
const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedIds.value = paginatedSubmissions.value.map(s => s.id);
    } else {
        selectedIds.value = [];
    }
};
const toggleAppraisalSelection = (id) => {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter(i => i !== id);
    } else {
        selectedIds.value = [...selectedIds.value, id];
    }
    selectAll.value = paginatedSubmissions.value.every(s => selectedIds.value.includes(s.id));
};
const clearSelection = () => { selectedIds.value = []; selectAll.value = false; };
const bulkApprove = async () => {
    if (!selectedIds.value.length) return;
    loading.value = true;
    try {
        await Promise.all(selectedIds.value.map(id =>
            axios.patch(`pms/appraisals/${id}/review`, { status: 'approved', hr_comments: '' })
        ));
        showAlert('Success', `${selectedIds.value.length} appraisals approved.`, 'success');
        selectedIds.value = [];
        selectAll.value = false;
        await fetchSubmissions();
    } catch (e) {
        showAlert('Error', 'Some approvals failed.', 'error');
    } finally { loading.value = false; }
};
const exportSelectedCSV = () => {
    const selected = submissions.value.filter(s => selectedIds.value.includes(s.id));
    if (!selected.length) { showAlert('Info', 'No items selected.', 'info'); return; }

    const firstAppraisal = parseSmart(selected[0].appraisal_data || '{}');
    const comps = firstAppraisal?.competencies || [];

    const headers = [
        'Employee Code', 'Employee Name', 'Department', 'Location', 'Line Manager',
        'Year', 'Status', 'Overall Rating', 'Goal Title', 'Category', 'Target %',
        'Description', 'Purposes'
    ];
    comps.forEach(c => { headers.push(`${c.title} (Self)`, `${c.title} (Mgr)`); });
    headers.push('Performance Rating', 'Potential Rating', 'What Impressed Most', 'What Impressed Least', 'General Comments');
    headers.push('Review: Achievements', 'Review: Initiatives', 'Review: Next Steps', 'Review: Improvements', 'Review: Manager Observations');
    headers.push('HOD Comments', 'Director Remarks');
    [1, 2, 3, 4].forEach(q => { headers.push(`Q${q} Target Measures`, `Q${q} Evidence`); });

    const rows = selected.map(s => {
        const ad = parseSmart(s.appraisal_data || '{}') || {};
        const row = [
            s.user?.employee_code || 'N/A', s.candidate_name || s.user?.name || 'N/A',
            s.user?.department || 'N/A', s.user?.location || 'N/A', s.manager_name || 'N/A',
            s.year, s.status, calculateAverageRating(s),
            s.title || 'N/A', s.category || 'General', `${s.target || 100}%`,
            Array.isArray(parseList(s.description)) ? parseList(s.description).join('; ') : (s.description || ''),
            Array.isArray(parseList(s.purposes)) ? parseList(s.purposes).join('; ') : (s.purposes || '')
        ];
        const competencyMap = ad?.competencies || [];
        comps.forEach((_, idx) => { const c = competencyMap[idx] || {}; row.push(c.selfRating || 0, c.managerRating || 0); });
        row.push(ad?.performanceRating || 'N/A', ad?.potentialRating || 'N/A',
            ad?.impressedMost || '', ad?.impressedLeast || '', ad?.comments || '');
        const rs = ad?.review_summary || {};
        const ach = rs.A ? (Array.isArray(rs.A) ? rs.A.map(a => `${a.achievement || ''} -> ${a.target || ''}`).join('; ') : rs.A) : '';
        row.push(ach);
        row.push(rs.B ? (Array.isArray(rs.B) ? rs.B.join('; ') : rs.B) : '');
        row.push(rs.C ? (Array.isArray(rs.C) ? rs.C.join('; ') : rs.C) : '');
        row.push(rs.D ? (Array.isArray(rs.D) ? rs.D.join('; ') : rs.D) : '');
        row.push(rs.E ? (Array.isArray(rs.E) ? rs.E.join('; ') : rs.E) : '');
        row.push(ad?.hod_comments || '', ad?.director_remarks || '');
        const quarters = parseList(s.quarterly_tracking);
        [0, 1, 2, 3].forEach(idx => {
            const q = quarters[idx] || {};
            row.push(Array.isArray(parseList(q.target_measures)) ? parseList(q.target_measures).join('; ') : 'N/A', q.evidence || '');
        });
        return row;
    });

    let csvContent = "﻿" + [headers, ...rows].map(e => e.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(",")).join("\n");
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", `pms_selected_export_${currentYear.value}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// --- Inline Review ---
const inlineRejectId = ref(null);
const inlineRejectComment = ref('');
const inlineActionLoading = ref(null);
const handleInlineAction = async (appraisal, status) => {
    if (status === 'rejected' && inlineRejectId.value !== appraisal.id) {
        inlineRejectId.value = appraisal.id;
        inlineRejectComment.value = '';
        return;
    }
    inlineActionLoading.value = appraisal.id;
    try {
        await axios.patch(`pms/appraisals/${appraisal.id}/review`, {
            status,
            hr_comments: status === 'rejected' ? inlineRejectComment.value : ''
        });
        showAlert('Success', `Appraisal ${status} successfully.`, 'success');
        inlineRejectId.value = null;
        inlineRejectComment.value = '';
        await fetchSubmissions();
    } catch (e) {
        showAlert('Error', 'Action failed. Please try again.', 'error');
    } finally { inlineActionLoading.value = null; }
};
const cancelInlineReject = () => { inlineRejectId.value = null; inlineRejectComment.value = ''; };

// --- Analytics ---
const ratingDistribution = computed(() => {
    const buckets = [
        { label: '0-1', min: 0, max: 1, count: 0, color: '#EF4444' },
        { label: '1-2', min: 1, max: 2, count: 0, color: '#F97316' },
        { label: '2-3', min: 2, max: 3, count: 0, color: '#EAB308' },
        { label: '3-4', min: 3, max: 4, count: 0, color: '#22C55E' },
        { label: '4-5', min: 4, max: 5.01, count: 0, color: '#10B981' }
    ];
    submissions.value.forEach(s => {
        const r = parseFloat(calculateAverageRating(s)) || 0;
        const bucket = buckets.find(b => r >= b.min && r < b.max);
        if (bucket) bucket.count++;
    });
    const max = Math.max(...buckets.map(b => b.count), 1);
    return buckets.map(b => ({ ...b, pct: Math.round((b.count / max) * 100) }));
});

const departmentBreakdown = computed(() => {
    const map = {};
    const colors = ['#6366F1', '#8B5CF6', '#EC4899', '#14B8A6', '#F59E0B', '#EF4444', '#3B82F6', '#10B981'];
    submissions.value.forEach(s => {
        const dept = s.user?.department || s.department || 'Other';
        map[dept] = (map[dept] || 0) + 1;
    });
    return Object.entries(map).map(([name, count], i) => ({
        name, count, color: colors[i % colors.length]
    }));
});

const getAppraisalCompetencies = (appraisal) => {
    try {
        const data = typeof appraisal.appraisal_data === 'string' ? JSON.parse(appraisal.appraisal_data) : (appraisal.appraisal_data || {});
        return data.competencies || [];
    } catch { return []; }
};

const getManagerRating = (goal) => {
    const data = typeof goal.appraisal_data === 'string' ? JSON.parse(goal.appraisal_data || '{}') : (goal.appraisal_data || {});
    const comps = data.competencies || [];
    const rated = comps.filter(c => parseFloat(c.managerRating) > 0);
    if (rated.length > 0) {
        const total = rated.reduce((sum, c) => sum + parseFloat(c.managerRating), 0);
        return (total / rated.length).toFixed(1);
    }
    if (goal.overall_rating) return parseFloat(goal.overall_rating).toFixed(1);
    return null;
};

const getSelfRating = (goal) => {
    const data = typeof goal.appraisal_data === 'string' ? JSON.parse(goal.appraisal_data || '{}') : (goal.appraisal_data || {});
    const comps = data.competencies || [];
    const rated = comps.filter(c => parseFloat(c.selfRating) > 0);
    if (rated.length > 0) {
        const total = rated.reduce((sum, c) => sum + parseFloat(c.selfRating), 0);
        return (total / rated.length).toFixed(1);
    }
    return null;
};

const triggerPrint = () => {
    const source = document.getElementById('protocol-report');
    if (!source) return;

    const printContainer = document.createElement('div');
    printContainer.id = 'print-clone';
    printContainer.innerHTML = source.innerHTML;
    document.body.appendChild(printContainer);

    const printStyle = document.createElement('style');
    printStyle.id = 'print-clone-style';
    printStyle.textContent = `
        @media print {
            body > *:not(#print-clone):not(#print-clone-style) {
                display: none !important;
            }
            #print-clone {
                display: block !important;
                position: static !important;
                width: 100% !important;
                background: #ffffff !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            #print-clone .hardcopy-page {
                margin: 0 !important;
                padding: 8mm 10mm !important;
                width: 100% !important;
                min-height: auto !important;
                max-height: none !important;
                height: auto !important;
                page-break-after: always !important;
                break-after: page !important;
                box-shadow: none !important;
                border: none !important;
                display: flex !important;
                flex-direction: column !important;
                overflow: visible !important;
            }
            #print-clone .hardcopy-page:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            #print-clone table {
                page-break-inside: auto !important;
            }
            #print-clone tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            #print-clone thead {
                display: table-header-group !important;
            }
            #print-clone .section-box, 
            #print-clone .quarterly-section, 
            #print-clone .feedback-split-box, 
            #print-clone .scale-guide-wrapper, 
            #print-clone .review-section-block, 
            #print-clone .auth-signoff-section {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            @page {
                size: A4 portrait;
                margin: 6mm 6mm;
            }
        }
        @media screen {
            #print-clone { display: none !important; }
        }
    `;
    document.head.appendChild(printStyle);

    setTimeout(() => {
        window.print();
        setTimeout(() => {
            if (document.body.contains(printContainer)) document.body.removeChild(printContainer);
            if (document.head.contains(printStyle)) document.head.removeChild(printStyle);
        }, 500);
    }, 300);
};

const getDossierId = (goal) => {
    if (!goal) return 'PMS-000';
    const deptPrefix = goal.department ? goal.department.substring(0, 3).toUpperCase() : (goal.user?.department ? goal.user.department.substring(0, 3).toUpperCase() : 'PMS');
    return `${deptPrefix}-${goal.employee_code || goal.user?.employee_code || goal.id}`;
};

const getStatusConfig = (status) => {
    const map = {
        'approved': { label: 'Approved', bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
        'completed': { label: 'Completed', bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500' },
        'review_completed': { label: 'Reviewed', bg: 'bg-indigo-50', text: 'text-indigo-700', dot: 'bg-indigo-500' },
        'rejected': { label: 'Rejected', bg: 'bg-rose-50', text: 'text-rose-700', dot: 'bg-rose-500' },
        'pending': { label: 'Pending', bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500' },
        'submitted': { label: 'Submitted', bg: 'bg-violet-50', text: 'text-violet-700', dot: 'bg-violet-500' },
        'draft': { label: 'Draft', bg: 'bg-slate-50', text: 'text-slate-600', dot: 'bg-slate-400' },
    };
    return map[status] || { label: status || 'Unknown', bg: 'bg-slate-50', text: 'text-slate-600', dot: 'bg-slate-400' };
};

const getRatingColor = (rating) => {
    const r = parseFloat(rating) || 0;
    if (r >= 4) return 'text-emerald-600';
    if (r >= 3) return 'text-blue-600';
    if (r >= 2) return 'text-amber-600';
    return 'text-rose-600';
};

const getRatingBadgeClass = (val) => {
    const num = parseFloat(val) || 0;
    if (num >= 4.0) return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    if (num >= 3.0) return 'bg-blue-50 text-blue-700 border border-blue-200';
    if (num >= 2.0) return 'bg-amber-50 text-amber-700 border border-amber-200';
    if (num > 0) return 'bg-rose-50 text-rose-700 border border-rose-200';
    return 'bg-slate-50 text-slate-400 border border-slate-200';
};
</script>

<template>
    <div class="submissions-wrapper min-h-screen">
        <div v-if="viewMode === 'list'">

            <!-- Page Header -->
            <div class="submissions-header-row mb-5">
                <div class="header-titles">
                    <div class="flex items-center gap-3">
                        <div class="header-icon-box">
                            <i class="pi pi-inbox text-xl text-indigo-600"></i>
                        </div>
                        <div>
                            <h2 class="page-main-title">PMS Submissions</h2>
                            <p class="page-subtitle">Performance appraisal submissions and evaluation records for {{ currentYear }}</p>
                        </div>
                    </div>
                </div>

                <div class="header-actions">
                    <!-- Year Switcher Pill -->
                    <div class="year-switcher-pill">
                        <button @click="currentYear--; fetchSubmissions()" class="year-btn" title="Previous Year">
                            <i class="pi pi-chevron-left text-[11px]"></i>
                        </button>
                        <span class="year-display">{{ currentYear }}</span>
                        <button @click="currentYear++; fetchSubmissions()" class="year-btn" title="Next Year">
                            <i class="pi pi-chevron-right text-[11px]"></i>
                        </button>
                    </div>

                    <!-- Export CSV -->
                    <button @click="exportSubmissionsCSV" class="btn-secondary-action" title="Export filtered submissions to CSV">
                        <i class="pi pi-download mr-1.5 text-xs"></i>
                        Export CSV
                    </button>
                </div>
            </div>

            <!-- KPI Stat Cards (4-Card Executive Grid) -->
            <div class="kpi-metrics-grid mb-4">
                <!-- Total Submissions -->
                <div class="metric-card">
                    <div class="metric-icon-wrap bg-indigo-50 text-indigo-600">
                        <i class="pi pi-inbox text-lg"></i>
                    </div>
                    <div class="metric-content">
                        <span class="metric-label">Total Submissions</span>
                        <span class="metric-val text-indigo-950">{{ stats.total }}</span>
                    </div>
                </div>

                <!-- Awaiting Review -->
                <div class="metric-card">
                    <div class="metric-icon-wrap bg-amber-50 text-amber-600">
                        <i class="pi pi-clock text-lg"></i>
                    </div>
                    <div class="metric-content">
                        <span class="metric-label">Awaiting Review</span>
                        <span class="metric-val text-amber-600">{{ stats.pending }}</span>
                    </div>
                </div>

                <!-- Approved & Completed -->
                <div class="metric-card">
                    <div class="metric-icon-wrap bg-emerald-50 text-emerald-600">
                        <i class="pi pi-check-circle text-lg"></i>
                    </div>
                    <div class="metric-content">
                        <span class="metric-label">Approved / Done</span>
                        <span class="metric-val text-emerald-600">{{ stats.approved + stats.completed }}</span>
                    </div>
                </div>

                <!-- Rated Submissions -->
                <div class="metric-card">
                    <div class="metric-icon-wrap bg-purple-50 text-purple-600">
                        <i class="pi pi-chart-bar text-lg"></i>
                    </div>
                    <div class="metric-content">
                        <div class="flex items-center justify-between">
                            <span class="metric-label">Rated Appraisals</span>
                            <span v-if="parseFloat(stats.avgRating) > 0" class="px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 text-[10px] font-bold">
                                Avg: {{ stats.avgRating }}
                            </span>
                        </div>
                        <span class="metric-val text-purple-950">{{ stats.rated }}</span>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions Bar -->
            <transition name="slide-down">
                <div v-if="selectedIds.length > 0" class="mb-3.5 p-3 rounded-xl flex items-center justify-between gap-3 shadow-sm" style="background: linear-gradient(135deg, #4f46e5, #7c3aed);">
                    <div class="flex items-center gap-3">
                        <span class="text-white text-xs font-bold flex items-center gap-1.5">
                            <i class="pi pi-check-square"></i>
                            <strong>{{ selectedIds.length }}</strong> submissions selected
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="exportSelectedCSV" class="px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white rounded-lg text-xs font-bold border-none cursor-pointer transition-all">
                            <i class="pi pi-download mr-1"></i> Export Selected
                        </button>
                        <button @click="clearSelection" class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white/90 rounded-lg text-xs font-bold border-none cursor-pointer transition-all">
                            Clear
                        </button>
                    </div>
                </div>
            </transition>

            <!-- Unified Filter Controls Card -->
            <div class="filter-controls-card">
                <div class="filter-grid">
                    <!-- Search Input (Clean width, NO overlapping icon, with clear button) -->
                    <div class="search-input-wrapper">
                        <input 
                            type="text" 
                            v-model="searchQuery" 
                            placeholder="Search name, code, title..."
                            class="search-input-field"
                        />
                        <button 
                            v-if="searchQuery" 
                            @click="searchQuery = ''" 
                            class="clear-search-btn"
                            title="Clear search"
                        >
                            <i class="pi pi-times text-xs"></i>
                        </button>
                    </div>

                    <!-- Status Filter -->
                    <div class="filter-select-wrapper">
                        <select v-model="filterStatus" class="filter-select-field">
                            <option value="all">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="submitted">Submitted</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="completed">Completed</option>
                            <option value="review_completed">Reviewed</option>
                        </select>
                    </div>

                    <!-- Department Filter -->
                    <div class="filter-select-wrapper">
                        <select v-model="filterDepartment" class="filter-select-field">
                            <option value="">All Departments</option>
                            <option v-for="dept in depts" :key="dept.id" :value="dept.name">{{ dept.name }}</option>
                        </select>
                    </div>

                    <!-- Location Filter -->
                    <div class="filter-select-wrapper">
                        <select v-model="filterLocation" class="filter-select-field">
                            <option value="">All Locations</option>
                            <option v-for="loc in branchs" :key="loc.id" :value="loc.name">{{ loc.name }}</option>
                        </select>
                    </div>

                    <!-- Date Range: From / To -->
                    <div class="date-filter-group">
                        <span class="date-label">From:</span>
                        <input v-model="dateFrom" type="date" class="date-input-field" />
                        <span class="date-label">To:</span>
                        <input v-model="dateTo" type="date" class="date-input-field" />
                    </div>

                    <!-- Reset Filters Button -->
                    <button 
                        v-if="activeFilterCount > 0" 
                        @click="clearAllFilters" 
                        class="reset-filters-btn"
                        title="Reset all active filters"
                    >
                        <i class="pi pi-filter-slash text-xs mr-1"></i>
                        Reset ({{ activeFilterCount }})
                    </button>

                    <!-- Rows per page: Placed at the LAST Column (aligned to the right) -->
                    <div class="rows-per-page-col">
                        <span class="rows-label">Rows per page:</span>
                        <select v-model="rowsPerPage" class="page-size-select">
                            <option :value="10">10</option>
                            <option :value="15">15</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                </div>

                <!-- Active Results Counter -->
                <div class="filter-footer-info">
                    <span class="results-count-text">
                        Showing <strong>{{ paginatedSubmissions.length }}</strong> of <strong>{{ filteredSubmissions.length }}</strong> submissions
                        <span v-if="activeFilterCount > 0" class="text-indigo-600 ml-1 font-semibold">(Filtered from {{ submissions.length }})</span>
                    </span>
                </div>
            </div>

            <!-- Modern Table Container -->
            <div class="table-outer-wrapper">
                <!-- Loading State -->
                <div v-if="loading" class="flex flex-col items-center justify-center py-20">
                    <div class="w-10 h-10 border-3 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="mt-3 text-xs font-semibold text-slate-500">Loading submissions...</p>
                </div>

                <!-- Empty State -->
                <div v-else-if="filteredSubmissions.length === 0" class="empty-state-card py-16 px-6 text-center">
                    <div class="empty-icon-wrap mb-4 mx-auto">
                        <i class="pi pi-folder-open text-3xl text-indigo-400"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">No Submissions Found for Year {{ currentYear }}</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                        {{ activeFilterCount > 0 ? 'No submissions match your currently selected filters. Try clearing or adjusting your filters.' : 'There are no performance appraisal submissions recorded for ' + currentYear + '.' }}
                    </p>
                    <div class="flex items-center justify-center gap-3 mt-4">
                        <button v-if="activeFilterCount > 0" @click="clearAllFilters" class="btn-empty-action">
                            <i class="pi pi-filter-slash mr-1.5"></i> Clear All Filters
                        </button>
                        <button @click="currentYear = currentYear - 1; fetchSubmissions()" class="btn-empty-outline">
                            <i class="pi pi-calendar mr-1.5"></i> View {{ currentYear - 1 }} Submissions
                        </button>
                    </div>
                </div>

                <!-- Table Data -->
                <div v-else class="overflow-x-auto">
                    <table class="modern-submissions-table">
                        <thead>
                            <tr>
                                <th style="width: 44px; text-align: center;">
                                    <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="rounded cursor-pointer accent-indigo-600" />
                                </th>
                                <th @click="setSorting('name')" style="cursor: pointer;" title="Sort by Employee Name">
                                    <div class="flex items-center gap-1.5">
                                        EMPLOYEE / CANDIDATE
                                        <i :class="sortIcon('name')" class="text-[10px]"></i>
                                    </div>
                                </th>
                                <th @click="setSorting('employee_code')" style="cursor: pointer; width: 110px;" title="Sort by Code">
                                    <div class="flex items-center gap-1.5">
                                        CODE
                                        <i :class="sortIcon('employee_code')" class="text-[10px]"></i>
                                    </div>
                                </th>
                                <th @click="setSorting('department')" style="cursor: pointer;" class="hidden lg:table-cell" title="Sort by Department">
                                    <div class="flex items-center gap-1.5">
                                        DEPARTMENT
                                        <i :class="sortIcon('department')" class="text-[10px]"></i>
                                    </div>
                                </th>
                                <th class="hidden xl:table-cell">LINE MANAGER</th>
                                <th @click="setSorting('submitted_at')" style="cursor: pointer; width: 120px;" title="Sort by Date">
                                    <div class="flex items-center gap-1.5">
                                        DATE
                                        <i :class="sortIcon('submitted_at')" class="text-[10px]"></i>
                                    </div>
                                </th>
                                <th @click="setSorting('rating')" style="cursor: pointer; width: 90px; text-align: center;" title="Sort by Rating">
                                    <div class="flex items-center justify-center gap-1.5">
                                        RATING
                                        <i :class="sortIcon('rating')" class="text-[10px]"></i>
                                    </div>
                                </th>
                                <th @click="setSorting('status')" style="cursor: pointer; width: 130px;" title="Sort by Status">
                                    <div class="flex items-center gap-1.5">
                                        STATUS
                                        <i :class="sortIcon('status')" class="text-[10px]"></i>
                                    </div>
                                </th>
                                <th style="width: 90px; text-align: center;">ACTIONS</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="appraisal in paginatedSubmissions" :key="appraisal.id" class="submission-table-row">
                                <!-- Checkbox -->
                                <td style="text-align: center;" @click.stop>
                                    <input type="checkbox" :checked="selectedIds.includes(appraisal.id)" @change="toggleAppraisalSelection(appraisal.id)" class="rounded cursor-pointer accent-indigo-600" />
                                </td>

                                <!-- Candidate / Title -->
                                <td>
                                    <div class="flex items-center gap-3">
                                        <img :src="'https://ui-avatars.com/api/?name='+encodeURIComponent(appraisal.candidate_name || appraisal.user?.name || 'U')+'&background=6366f1&color=fff&size=80&bold=true'" class="w-8 h-8 rounded-xl object-cover shadow-sm shrink-0" />
                                        <div class="min-w-0">
                                            <span class="text-[13px] font-bold block truncate text-slate-800">{{ appraisal.candidate_name || appraisal.user?.name || 'Staff' }}</span>
                                            <span class="text-[11px] block truncate text-slate-400 font-medium">{{ appraisal.title || 'Goal Setting' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Code -->
                                <td>
                                    <span class="code-mono-badge">
                                        {{ appraisal.user?.employee_code || appraisal.employee_code || '---' }}
                                    </span>
                                </td>

                                <!-- Department -->
                                <td class="hidden lg:table-cell">
                                    <span class="dept-text font-semibold text-slate-700">
                                        <i class="pi pi-building text-slate-400 mr-1 text-xs"></i>
                                        {{ appraisal.user?.department || appraisal.department || 'N/A' }}
                                    </span>
                                </td>

                                <!-- Manager -->
                                <td class="hidden xl:table-cell">
                                    <span class="text-xs text-slate-600 font-medium">
                                        {{ appraisal.manager_name || 'N/A' }}
                                    </span>
                                </td>

                                <!-- Date -->
                                <td>
                                    <span class="text-xs text-slate-600 font-medium">
                                        {{ formatDate(appraisal.submitted_at || appraisal.created_at) }}
                                    </span>
                                </td>

                                <!-- Rating -->
                                <td style="text-align: center;">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg text-xs font-bold" :class="getRatingBadgeClass(calculateAverageRating(appraisal))">
                                        ★ {{ calculateAverageRating(appraisal) }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold" :class="[getStatusConfig(appraisal.status).bg, getStatusConfig(appraisal.status).text]">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusConfig(appraisal.status).dot"></span>
                                        {{ getStatusConfig(appraisal.status).label }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td style="text-align: center;" @click.stop>
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button @click="openActionModal(appraisal)" class="action-btn-view" title="View Dossier">
                                            <i class="pi pi-eye text-xs"></i>
                                        </button>
                                        <button @click="downloadSinglePDF(appraisal)" class="action-btn-pdf" title="Download PDF">
                                            <i class="pi pi-file-pdf text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="!loading && filteredSubmissions.length > 0" class="pagination-footer-row">
                    <span class="text-xs font-medium text-slate-500">
                        Showing {{ (currentPage - 1) * rowsPerPage + 1 }} - {{ Math.min(currentPage * rowsPerPage, filteredSubmissions.length) }} of {{ filteredSubmissions.length }}
                    </span>
                    <div class="flex items-center gap-1">
                        <button @click="currentPage = 1" :disabled="currentPage === 1" class="page-nav-btn" title="First Page">
                            <i class="pi pi-angle-double-left text-[10px]"></i>
                        </button>
                        <button @click="currentPage--" :disabled="currentPage === 1" class="page-nav-btn" title="Previous Page">
                            <i class="pi pi-chevron-left text-[10px]"></i>
                        </button>
                        <template v-for="page in totalPages" :key="page">
                            <button 
                                v-if="page === 1 || page === totalPages || (page >= currentPage - 1 && page <= currentPage + 1)"
                                @click="currentPage = page"
                                :class="['page-num-btn', { 'active': currentPage === page }]"
                            >
                                {{ page }}
                            </button>
                            <span v-else-if="page === currentPage - 2 || page === currentPage + 2" class="text-xs px-1 text-slate-400">...</span>
                        </template>
                        <button @click="currentPage++" :disabled="currentPage === totalPages" class="page-nav-btn" title="Next Page">
                            <i class="pi pi-chevron-right text-[10px]"></i>
                        </button>
                        <button @click="currentPage = totalPages" :disabled="currentPage === totalPages" class="page-nav-btn" title="Last Page">
                            <i class="pi pi-angle-double-right text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div> <!-- End viewMode === 'list' -->

        <!-- Dossier-Style Full-Screen Preview Dialog -->
        <Dialog v-model:visible="showActionModal" :modal="true" :showHeader="false"
            class="!p-0 overflow-hidden shadow-2xl border-none"
            :style="{ width: '100vw', height: '100vh', maxWidth: '100vw', maxHeight: '100vh', margin: '0' }"
            :contentStyle="{ padding: '0', backgroundColor: isDark ? '#0f172a' : '#F1F5F9', display: 'flex' }">

            <div v-if="selectedAppraisal" class="flex w-full h-full overflow-hidden">

                <!-- Document Viewport (Left/Center) -->
                <div class="flex-1 overflow-y-auto bg-slate-200 p-8 md:p-12 lg:p-20 flex flex-col items-center custom-scrollbar scroll-smooth">
                    <div id="protocol-report" class="w-full max-w-[210mm] print:m-0 print:shadow-none print:w-full no-scrollbar">
                        <PrintableHardCopyDossier :goal="selectedAppraisal" />
                    </div>
                </div>

                <!-- Right Sidebar (Controls) -->
                <div class="w-[380px] bg-slate-900 flex flex-col no-print shrink-0 border-l border-white/5">

                    <!-- Close Button -->
                    <div class="p-4 flex justify-end">
                        <button @click="showActionModal = false" class="w-10 h-10 rounded-full bg-white/5 hover:bg-white/10 text-white flex items-center justify-center transition-all cursor-pointer border-none">
                            <i class="pi pi-times"></i>
                        </button>
                    </div>

                    <div class="p-10 flex-1 overflow-y-auto no-scrollbar">

                        <!-- Visual Header -->
                        <div class="mb-10 text-center">
                            <div class="inline-flex w-24 h-24 rounded-3xl bg-red-500/10 text-red-500 items-center justify-center mb-6 shadow-2xl shadow-red-500/20 border border-red-500/20">
                                <i class="pi pi-file-pdf text-4xl"></i>
                            </div>
                            <h2 class="text-xl font-black text-white uppercase tracking-tight mb-2">Appraisal Dossier</h2>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ getDossierId(selectedAppraisal) }} // VERIFIED DOCUMENT</p>
                        </div>

                        <!-- Brief Description -->
                        <div class="mb-10 space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-1 h-4 bg-indigo-500"></div>
                                <h3 class="text-[10px] font-black text-white uppercase tracking-widest">Quick Brief</h3>
                            </div>
                            <div class="p-5 bg-white/5 rounded-2xl border border-white/5 text-[11px] font-medium text-slate-400 leading-relaxed italic">
                                "{{ selectedAppraisal.title }}: A strategic goal focused on {{ (selectedAppraisal.category || 'performance').toLowerCase() }} optimization for the {{ selectedAppraisal.year }} PMS cycle."
                            </div>
                        </div>

                        <!-- Metadata -->
                        <div class="grid grid-cols-2 gap-4 mb-10">
                            <div class="p-4 bg-white/5 rounded-xl border border-white/5">
                                <span class="text-[8px] font-black text-slate-500 uppercase block mb-1">Status</span>
                                <span class="text-[10px] font-black uppercase" :class="getStatusConfig(selectedAppraisal.status).text">{{ getStatusConfig(selectedAppraisal.status).label }}</span>
                            </div>
                            <div class="p-4 bg-white/5 rounded-xl border border-white/5">
                                <span class="text-[8px] font-black text-slate-500 uppercase block mb-1">Rating</span>
                                <span class="text-[10px] font-black text-white uppercase">{{ getManagerRating(selectedAppraisal) || calculateAverageRating(selectedAppraisal) }}/5</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-3">
                            <button @click="downloadPDF"
                                class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] flex items-center justify-center gap-3 transition-all active:scale-[0.98] shadow-xl shadow-indigo-600/20 border-none cursor-pointer">
                                <i class="pi pi-download"></i> Download PDF
                            </button>
                            <div class="grid grid-cols-2 gap-3">
                                <button @click="downloadCSV"
                                    class="py-4 bg-white/5 hover:bg-white/10 text-slate-200 border border-white/10 rounded-2xl text-[9px] font-black uppercase tracking-widest flex items-center justify-center gap-2 transition-all cursor-pointer">
                                    <i class="pi pi-file-excel"></i> CSV
                                </button>
                                <button @click="triggerPrint"
                                    class="py-4 bg-white/5 hover:bg-white/10 text-slate-200 border border-white/10 rounded-2xl text-[9px] font-black uppercase tracking-widest flex items-center justify-center gap-2 transition-all cursor-pointer">
                                    <i class="pi pi-print"></i> Print
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Info -->
                    <div class="p-10 border-t border-white/5">
                        <div class="flex items-center gap-4">
                            <img :src="'https://ui-avatars.com/api/?name='+encodeURIComponent(selectedAppraisal.candidate_name || selectedAppraisal.user?.name || 'E')+'&background=fff&color=1e293b&size=64'" class="w-10 h-10 rounded-xl" />
                            <div>
                                <p class="text-[10px] font-black text-white uppercase tracking-tight">{{ selectedAppraisal.candidate_name || selectedAppraisal.user?.name || 'Employee' }}</p>
                                <p class="text-[8px] font-bold text-slate-500 uppercase">{{ selectedAppraisal.user?.employee_code || selectedAppraisal.employee_code || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </Dialog>

    </div>
</template>

<style scoped>
/* ==========================================================================
   MODERN PMS SUBMISSIONS VIEW STYLES
   ========================================================================== */

/* Header Row */
.submissions-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.header-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #eef2ff;
    border: 1px solid #e0e7ff;
    display: flex;
    align-items: center;
    justify-content: center;
}

.page-main-title {
    font-size: 1.25rem;
    font-weight: 800;
    color: #1e293b;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.page-subtitle {
    font-size: 0.775rem;
    color: #64748b;
    margin-top: 2px;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Year Switcher Pill */
.year-switcher-pill {
    display: inline-flex;
    align-items: center;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 3px 4px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}

.year-btn {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    border: none;
    background: transparent;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s;
}

.year-btn:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.year-display {
    padding: 0 10px;
    font-size: 0.8125rem;
    font-weight: 800;
    color: #1e293b;
    user-select: none;
}

.btn-secondary-action {
    display: inline-flex;
    align-items: center;
    padding: 7px 14px;
    font-size: 0.775rem;
    font-weight: 700;
    color: #334155;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    cursor: pointer;
    transition: all 0.15s;
}

.btn-secondary-action:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
}

/* 4-Card Executive KPI Metrics Grid */
.kpi-metrics-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

@media (max-width: 1024px) {
    .kpi-metrics-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .kpi-metrics-grid {
        grid-template-columns: 1fr;
    }
}

.metric-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.metric-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.06);
}

.metric-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.metric-content {
    flex: 1;
    min-width: 0;
}

.metric-label {
    font-size: 0.6875rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: block;
}

.metric-val {
    font-size: 1.35rem;
    font-weight: 800;
    line-height: 1.2;
    margin-top: 2px;
    display: block;
}

/* Unified Filter Controls Card */
.filter-controls-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 12px 16px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}

.filter-grid {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    width: 100%;
}

/* Search input with NO overlapping icon, clean padding, and clear button */
.search-input-wrapper {
    position: relative;
    width: 220px;
    flex-shrink: 0;
}

.search-input-field {
    width: 100%;
    height: 36px;
    padding: 6px 28px 6px 12px;
    font-size: 0.8125rem;
    font-weight: 500;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #ffffff;
    color: #1e293b;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.search-input-field:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
}

.clear-search-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 2px;
    line-height: 1;
    border-radius: 4px;
}

.clear-search-btn:hover {
    color: #475569;
}

/* Select Filters */
.filter-select-wrapper {
    min-width: 130px;
}

.filter-select-field {
    width: 100%;
    height: 36px;
    padding: 6px 10px;
    font-size: 0.8125rem;
    font-weight: 500;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #ffffff;
    color: #334155;
    outline: none;
    cursor: pointer;
    transition: border-color 0.15s;
}

.filter-select-field:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
}

/* Date Range Filter Group */
.date-filter-group {
    display: flex;
    align-items: center;
    gap: 6px;
}

.date-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    white-space: nowrap;
}

.date-input-field {
    height: 36px;
    padding: 4px 8px;
    font-size: 0.775rem;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #ffffff;
    color: #334155;
    outline: none;
    transition: border-color 0.15s;
}

.date-input-field:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
}

.reset-filters-btn {
    height: 36px;
    padding: 0 12px;
    font-size: 0.775rem;
    font-weight: 700;
    color: #dc2626;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    transition: all 0.15s;
}

.reset-filters-btn:hover {
    background: #fee2e2;
}

/* Rows per page: Placed at the LAST Column on the far right */
.rows-per-page-col {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 8px;
}

.rows-label {
    font-size: 0.775rem;
    font-weight: 600;
    color: #64748b;
    white-space: nowrap;
}

.page-size-select {
    height: 36px;
    padding: 4px 8px;
    font-size: 0.8125rem;
    font-weight: 700;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #ffffff;
    color: #1e293b;
    outline: none;
    cursor: pointer;
    transition: border-color 0.15s;
}

.page-size-select:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
}

.filter-footer-info {
    margin-top: 10px;
    padding-top: 8px;
    border-top: 1px dashed #f1f5f9;
}

.results-count-text {
    font-size: 0.75rem;
    color: #64748b;
}

/* Modern Data Table Card & Styles */
.table-outer-wrapper {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.modern-submissions-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

/* Table Header: Faint dark navy / slate header matching UsersView */
.modern-submissions-table thead tr {
    background: #1e293b !important;
    border-bottom: 2px solid #0f172a;
}

.modern-submissions-table thead th {
    padding: 12px 14px;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #f1f5f9 !important;
    border: none;
    white-space: nowrap;
    user-select: none;
}

.modern-submissions-table thead th:hover {
    color: #ffffff !important;
}

/* Alternating zebra rows */
.modern-submissions-table tbody tr.submission-table-row {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s ease;
}

.modern-submissions-table tbody tr.submission-table-row:nth-child(even) {
    background-color: #f8fafc;
}

.modern-submissions-table tbody tr.submission-table-row:hover {
    background-color: #eef2ff !important;
}

.modern-submissions-table tbody td {
    padding: 12px 14px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}

/* Mono Code Badge */
.code-mono-badge {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.75rem;
    font-weight: 700;
    color: #475569;
    background: #f1f5f9;
    padding: 3px 8px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    letter-spacing: 0.02em;
    display: inline-block;
}

.dept-text {
    font-size: 0.8125rem;
    display: inline-flex;
    align-items: center;
}

/* Action Buttons */
.action-btn-view {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #eef2ff;
    color: #4f46e5;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
}

.action-btn-view:hover {
    background: #e0e7ff;
    color: #3730a3;
}

.action-btn-pdf {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #fef2f2;
    color: #dc2626;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
}

.action-btn-pdf:hover {
    background: #fee2e2;
    color: #b91c1c;
}

/* Pagination Footer */
.pagination-footer-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

.page-nav-btn {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s;
}

.page-nav-btn:hover:not(:disabled) {
    background: #f1f5f9;
    color: #0f172a;
}

.page-nav-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.page-num-btn {
    min-width: 30px;
    height: 30px;
    padding: 0 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s;
}

.page-num-btn:hover {
    background: #f1f5f9;
}

.page-num-btn.active {
    background: #4f46e5;
    border-color: #4f46e5;
    color: #ffffff;
}

/* Empty State */
.empty-state-card {
    background: #ffffff;
}

.empty-icon-wrap {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    background: #eef2ff;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-empty-action {
    display: inline-flex;
    align-items: center;
    padding: 8px 16px;
    font-size: 0.775rem;
    font-weight: 700;
    color: #ffffff;
    background: #4f46e5;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.15s;
}

.btn-empty-action:hover {
    background: #4338ca;
}

.btn-empty-outline {
    display: inline-flex;
    align-items: center;
    padding: 8px 16px;
    font-size: 0.775rem;
    font-weight: 700;
    color: #334155;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s;
}

.btn-empty-outline:hover {
    background: #f8fafc;
}

/* Dark Mode Overrides for Submissions View */
:global(body.dark-mode) .submissions-header-row .page-main-title {
    color: #ffffff;
}

:global(body.dark-mode) .submissions-header-row .page-subtitle {
    color: #94a3b8;
}

:global(body.dark-mode) .header-icon-box {
    background: rgba(99, 102, 241, 0.15);
    border-color: rgba(99, 102, 241, 0.25);
}

:global(body.dark-mode) .year-switcher-pill,
:global(body.dark-mode) .btn-secondary-action,
:global(body.dark-mode) .metric-card,
:global(body.dark-mode) .filter-controls-card,
:global(body.dark-mode) .table-outer-wrapper,
:global(body.dark-mode) .empty-state-card {
    background: #1e293b;
    border-color: #334155;
}

:global(body.dark-mode) .year-display,
:global(body.dark-mode) .btn-secondary-action,
:global(body.dark-mode) .metric-val {
    color: #f8fafc;
}

:global(body.dark-mode) .search-input-field,
:global(body.dark-mode) .filter-select-field,
:global(body.dark-mode) .date-input-field,
:global(body.dark-mode) .page-size-select {
    background: #0f172a;
    border-color: #334155;
    color: #f8fafc;
}

:global(body.dark-mode) .code-mono-badge {
    background: #0f172a;
    border-color: #334155;
    color: #94a3b8;
}

:global(body.dark-mode) .modern-submissions-table tbody tr.submission-table-row {
    border-bottom-color: #334155;
}

:global(body.dark-mode) .modern-submissions-table tbody tr.submission-table-row:nth-child(even) {
    background-color: rgba(15, 23, 42, 0.4);
}

:global(body.dark-mode) .modern-submissions-table tbody tr.submission-table-row:hover {
    background-color: rgba(99, 102, 241, 0.12) !important;
}

:global(body.dark-mode) .pagination-footer-row {
    background: #1e293b;
    border-top-color: #334155;
}

:global(body.dark-mode) .page-nav-btn,
:global(body.dark-mode) .page-num-btn {
    background: #0f172a;
    border-color: #334155;
    color: #94a3b8;
}

:global(body.dark-mode) .page-num-btn.active {
    background: #4f46e5;
    border-color: #4f46e5;
    color: #ffffff;
}

:global(body.dark-mode) .empty-icon-wrap {
    background: rgba(99, 102, 241, 0.15);
}

:global(body.dark-mode) .empty-state-card h3 {
    color: #f8fafc;
}

:global(body.dark-mode) .empty-state-card p {
    color: #94a3b8;
}

:global(body.dark-mode) .btn-empty-outline {
    background: #1e293b;
    border-color: #334155;
    color: #f8fafc;
}

.watermark-logo {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 280px;
    height: auto;
    opacity: 0.04;
    pointer-events: none;
    z-index: 0;
    user-select: none;
}
.doc-page > *:not(.watermark-logo) {
    position: relative;
    z-index: 1;
}

/* Table row hover */
.table-row-hover:hover {
    background: var(--surface-ground, #f8fafc) !important;
}

/* Stat card subtle hover */
.stat-card:hover {
    transform: translateY(-1px);
}

/* Slider Styling for Webkit */
input[type=range]::-webkit-slider-thumb {
  -webkit-appearance: none;
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #7c3aed;
  cursor: pointer;
  margin-top: -6px;
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
}

input[type=range]::-webkit-slider-runnable-track {
  width: 100%;
  height: 8px;
  cursor: pointer;
  background: #e2e8f0;
  border-radius: 4px;
}

/* HIGH FIDELITY PRINT OVERRIDES */
@media print {
  .no-print,
  nav, header, footer, aside {
    display: none !important;
  }

  body, html {
    background: white !important;
    margin: 0 !important;
    padding: 0 !important;
    height: auto !important;
    overflow: visible !important;
  }

  .p-dialog-mask,
  .p-dialog,
  .p-dialog-content {
    position: static !important;
    overflow: visible !important;
    height: auto !important;
    max-height: none !important;
    width: 100% !important;
    max-width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
    background: white !important;
    display: block !important;
  }

  #protocol-report {
    position: static !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    display: block !important;
    overflow: visible !important;
    background: white !important;
  }

  .doc-page {
    margin: 0 !important;
    padding: 10mm !important;
    width: 100% !important;
    min-height: auto !important;
    max-height: none !important;
    height: auto !important;
    page-break-after: always !important;
    break-after: page !important;
    box-shadow: none !important;
    border: none !important;
    display: block !important;
    overflow: visible !important;
    border-radius: 0 !important;
  }

  .doc-page:last-child {
    page-break-after: avoid !important;
  }

  table { page-break-inside: auto !important; }
  tr { page-break-inside: avoid !important; break-inside: avoid !important; }
  thead { display: table-header-group !important; }
  .space-y-4 > *, .space-y-6 > *, .space-y-8 > *, .space-y-10 > * {
    page-break-inside: avoid !important;
    break-inside: avoid !important;
  }
  .grid {
    page-break-inside: avoid !important;
    break-inside: avoid !important;
  }
  .mt-auto { margin-top: 10mm !important; }

  .text-slate-600, .text-slate-700 {
    color: #1e293b !important;
  }

  @page {
    size: A4 portrait;
    margin: 8mm 5mm;
  }
}

/* Dark Mode */
:global(body.dark-mode) input[type=range]::-webkit-slider-runnable-track {
    background: #475569 !important;
}

/* Dark mode KPI icon backgrounds */
:global(body.dark-mode) .bg-indigo-50 { background: rgba(99, 102, 241, 0.15) !important; }
:global(body.dark-mode) .bg-emerald-50 { background: rgba(16, 185, 129, 0.15) !important; }
:global(body.dark-mode) .bg-amber-50 { background: rgba(245, 158, 11, 0.15) !important; }
:global(body.dark-mode) .bg-rose-50 { background: rgba(244, 63, 94, 0.15) !important; }
:global(body.dark-mode) .bg-violet-50 { background: rgba(139, 92, 246, 0.15) !important; }
:global(body.dark-mode) .bg-cyan-50 { background: rgba(6, 182, 212, 0.15) !important; }

/* Dark mode status pills */
:global(body.dark-mode) .text-emerald-600.bg-emerald-50,
:global(body.dark-mode) .bg-emerald-50.text-emerald-700 { background: rgba(16, 185, 129, 0.15) !important; }
:global(body.dark-mode) .text-amber-600.bg-amber-50,
:global(body.dark-mode) .bg-amber-50.text-amber-700 { background: rgba(245, 158, 11, 0.15) !important; }
:global(body.dark-mode) .text-rose-600.bg-rose-50,
:global(body.dark-mode) .bg-rose-50.text-rose-700 { background: rgba(244, 63, 94, 0.15) !important; }
:global(body.dark-mode) .bg-violet-50.text-violet-700 { background: rgba(139, 92, 246, 0.15) !important; }
:global(body.dark-mode) .bg-blue-50.text-blue-700 { background: rgba(59, 130, 246, 0.15) !important; }
:global(body.dark-mode) .bg-indigo-50.text-indigo-700 { background: rgba(99, 102, 241, 0.15) !important; }
:global(body.dark-mode) .bg-slate-50.text-slate-600 { background: rgba(100, 116, 139, 0.15) !important; }

/* Dark mode table row hover */
:global(body.dark-mode) .table-row-hover:hover {
    background: var(--surface-ground, #1e293b) !important;
}

/* Slide-down transition */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-12px);
}

/* Custom scrollbar for dossier viewport */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.25); }

.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
