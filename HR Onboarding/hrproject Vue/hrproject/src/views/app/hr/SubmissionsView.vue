<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from '@/helpers/pms_axios';
import { useUsersStore } from '@/stores/user';
import { showAlert, parseSmart, parseList, calculateAverageRating } from '@/helpers/essential';
import { depts, branchs } from '@/data/masterdata';
import melcomLogo from '@/assets/img/melcom_logo.png';

const userstore = useUsersStore();

const submissions = ref([]);
const loading = ref(true);
watch(loading, (val) => userstore.setIsLoading(val), { immediate: true });
const selectedAppraisal = ref(null);
const showActionModal = ref(false);
const actionData = ref({ status: 'approved', hr_comments: '', overall_rating: 4.0 });
const viewMode = ref('list'); // kept for compatibility

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

const filterStatus = ref('all');
const filterDepartment = ref('');
const filterLocation = ref('');
const searchQuery = ref('');
const expandedManagers = ref([]);

const toggleManager = (name) => {
    if (expandedManagers.value.includes(name)) {
        expandedManagers.value = expandedManagers.value.filter(m => m !== name);
    } else {
        expandedManagers.value.push(name);
    }
};
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
    const rated = all.filter(s => parseFloat(calculateAverageRating(s)) > 0);
    const avgRating = rated.length > 0
        ? (rated.reduce((sum, s) => sum + parseFloat(calculateAverageRating(s)), 0) / rated.length).toFixed(1)
        : '0.0';
    return {
        total: all.length,
        rated: rated.length,
        avgRating: avgRating
    };
});

const groupedSubmissions = computed(() => {
    const groups = {};
    submissions.value.forEach(s => {
        const mgr = s.manager_name || 'Administrator';
        if (!groups[mgr]) {
            groups[mgr] = {
                manager_name: mgr,
                department: s.user?.department || 'NA',
                user: s.user, 
                appraisals: []
            };
        }
        groups[mgr].appraisals.push(s);
    });

    return Object.values(groups).map(g => {
        const matchingAppraisals = g.appraisals.filter(s => {
            const query = searchQuery.value.toLowerCase();
            const name = s.user?.name?.toLowerCase() || '';
            const code = s.user?.employee_code?.toLowerCase() || '';
            const record_id = getRecordId(s).toLowerCase();

            let matchesSearch = true;
            if (query) {
                matchesSearch = name.includes(query) || code.includes(query) || record_id.includes(query) || g.manager_name.toLowerCase().includes(query);
            }

            let matchesStatus = true;
            if (filterStatus.value !== 'all') {
                matchesStatus = s.status === filterStatus.value;
            }

            let matchesDept = true;
            if (filterDepartment.value) {
                matchesDept = (s.user?.department === filterDepartment.value) || (s.department === filterDepartment.value);
            }

            let matchesLoc = true;
            if (filterLocation.value) {
                matchesLoc = (s.user?.location === filterLocation.value) || (s.location === filterLocation.value);
            }

            return matchesSearch && matchesStatus && matchesDept && matchesLoc;
        });
        return { ...g, appraisals: matchingAppraisals };
    }).filter(g => g.appraisals.length > 0);
});

// Create flat fallback for empty states and stats counters backwards compatibility
const filteredSubmissions = computed(() => {
    return groupedSubmissions.value.flatMap(g => g.appraisals);
});

const fetchSubmissions = async () => {
    loading.value = true;
    const { setIsLoading } = userstore; // Ensure we have the store setter
    // Use the store instance directly
    userstore.setIsLoading(true);
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
        userstore.setIsLoading(false);
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

    // Comprehensive horizontal headers
    const headers = [
        'Employee Code', 'Employee Name', 'Department', 'Location', 'Job Title', 'Line Manager',
        'Year', 'Status', 'Goal Title', 'Category', 'Target %',
        'Description/Objectives', 'Purposes', 'Challenges',
        'SMART-Specific', 'SMART-Measurable', 'SMART-Attainable', 'SMART-Relevant', 'SMART-TimeBound'
    ];

    // Quarterly headers
    [1, 2, 3, 4].forEach(q => {
        headers.push(`Q${q} Start Date`, `Q${q} End Date`, `Q${q} Target Measures`, `Q${q} Evidence`);
    });

    // Competency headers
    comps.forEach(c => {
        headers.push(`${c.title} (Weight)`, `${c.title} (Self)`, `${c.title} (Mgr)`);
    });

    // Appraisal & review fields
    headers.push(
        'Overall Manager Rating', 'Performance Rating', 'Potential Rating',
        'What Impressed Most', 'What Impressed Least', 'General Comments',
        'HOD Comments', 'Director Remarks',
        'Employee Signature', 'Manager Signature', 'Signature Date',
        'HOD Signature', 'HOD Signature Date', 'Director Signature', 'Director Signature Date'
    );

    // Review summary headers
    headers.push('Achievements vs Targets', 'Initiatives Taken', 'Next Steps', 'Areas for Improvement', 'Manager Observations');

    // Build data row
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

    // Quarterly data
    const quarters = parseList(s.quarterly_tracking);
    [0, 1, 2, 3].forEach(idx => {
        const q = quarters[idx] || {};
        row.push(q.start_date || 'N/A', q.end_date || 'N/A',
            Array.isArray(parseList(q.target_measures)) ? parseList(q.target_measures).join('; ') : (q.target_measures || 'N/A'),
            q.evidence || '');
    });

    // Competency data
    comps.forEach(c => {
        row.push(`${c.weight}%`, c.selfRating || 0, c.managerRating || 0);
    });

    // Appraisal data
    row.push(
        calculateAverageRating(s) || 'N/A', ad.performanceRating || 'N/A', ad.potentialRating || 'N/A',
        ad.impressedMost || '', ad.impressedLeast || '', ad.comments || '',
        ad.hod_comments || '', ad.director_remarks || '',
        ad.candidate_signature_name || '', ad.manager_signature_name || '', ad.signature_date || '',
        ad.hod_signature_name || '', ad.hod_signature_date || '', ad.director_signature_name || '', ad.director_signature_date || ''
    );

    // Review summary
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

    // Determine competency headers from the first item
    const firstAppraisal = parseSmart(data[0].appraisal_data || '{}');
    const comps = firstAppraisal?.competencies || [];
    
    const headers = [
        'Employee Code', 'Employee Name', 'Department', 'Location', 'Line Manager', 
        'Year', 'Status', 'Overall Rating', 'Submitted At',
        'Goal Title', 'Goal Description', 'Goal Purposes'
    ];
    
    // Add competency headers
    comps.forEach(c => {
        headers.push(`${c.title} (Self)`, `${c.title} (Mgr)`);
    });
    
    // Appraisal detail headers
    headers.push('Performance Rating', 'Potential Rating', 'What Impressed Most', 'What Impressed Least', 'General Comments');

    // Add feedback headers
    headers.push('Review: Achievements', 'Review: Initiatives', 'Review: Next Steps', 'Review: Improvements', 'Review: Manager Observations');

    // HOD & Director
    headers.push('HOD Comments', 'Director Remarks',
        'Employee Signature', 'Manager Signature', 'Signature Date',
        'HOD Signature', 'HOD Signature Date', 'Director Signature', 'Director Signature Date');

    // Add Quarterly Data Headers
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
            s.year,
            s.status,
            calculateAverageRating(s),
            s.submitted_at || s.created_at || 'N/A',
            s.title || 'N/A',
            Array.isArray(parseList(s.description)) ? parseList(s.description).join('; ') : (s.description || 'N/A'),
            Array.isArray(parseList(s.purposes)) ? parseList(s.purposes).join('; ') : (s.purposes || 'N/A')
        ];
        
        // Competency scores
        const competencyMap = ad?.competencies || [];
        comps.forEach((headerComp, idx) => {
             const c = competencyMap[idx] || {};
             row.push(c.selfRating || 0, c.managerRating || 0);
        });
        
        // Appraisal details
        row.push(ad?.performanceRating || 'N/A', ad?.potentialRating || 'N/A',
            ad?.impressedMost || '', ad?.impressedLeast || '', ad?.comments || '');

        // Review Summaries
        const rs = ad?.review_summary || {};
        const ach = rs.A ? (Array.isArray(rs.A) ? rs.A.map(a => `${a.achievement || ''} -> ${a.target || ''}`).join('; ') : rs.A) : '';
        row.push(ach);
        row.push(rs.B ? (Array.isArray(rs.B) ? rs.B.join('; ') : rs.B) : '');
        row.push(rs.C ? (Array.isArray(rs.C) ? rs.C.join('; ') : rs.C) : '');
        row.push(rs.D ? (Array.isArray(rs.D) ? rs.D.join('; ') : rs.D) : '');
        row.push(rs.E ? (Array.isArray(rs.E) ? rs.E.join('; ') : rs.E) : '');

        // HOD & Director + Signatures
        row.push(ad?.hod_comments || '', ad?.director_remarks || '',
            ad?.candidate_signature_name || '', ad?.manager_signature_name || '', ad?.signature_date || '',
            ad?.hod_signature_name || '', ad?.hod_signature_date || '', ad?.director_signature_name || '', ad?.director_signature_date || '');

        // Add Quarterly Data
        const quarters = parseList(s.quarterly_tracking);
        [0, 1, 2, 3].forEach(idx => {
            const q = quarters[idx] || {};
            const measures = Array.isArray(parseList(q.target_measures)) ? parseList(q.target_measures).join('; ') : (q.target_measures || 'N/A');
            row.push(q.start_date || 'N/A', q.end_date || 'N/A', measures, q.evidence || '');
        });
        
        return row;
    });

    let csvContent = "\uFEFF" + [headers, ...rows].map(e => e.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(",")).join("\n");
        
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
          margin:       8,
          filename:     `${selectedAppraisal.value.user?.name || 'employee'}_pms_${selectedAppraisal.value.year}.pdf`,
          image:        { type: 'jpeg', quality: 0.98 },
          html2canvas:  { scale: 2, useCORS: true },
          jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
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
const isManagerGroupSelected = (appraisals) => appraisals.every(a => selectedIds.value.includes(a.id));
const toggleManagerGroup = (appraisals) => {
    if (isManagerGroupSelected(appraisals)) {
        selectedIds.value = selectedIds.value.filter(id => !appraisals.some(a => a.id === id));
    } else {
        const newIds = appraisals.filter(a => !selectedIds.value.includes(a.id)).map(a => a.id);
        selectedIds.value = [...selectedIds.value, ...newIds];
    }
};
const toggleAppraisalSelection = (id) => {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter(i => i !== id);
    } else {
        selectedIds.value = [...selectedIds.value, id];
    }
};
const clearSelection = () => { selectedIds.value = []; };
const bulkApprove = async () => {
    if (!selectedIds.value.length) return;
    loading.value = true;
    try {
        await Promise.all(selectedIds.value.map(id =>
            axios.patch(`pms/appraisals/${id}/review`, { status: 'approved', hr_comments: '' })
        ));
        showAlert('Success', `${selectedIds.value.length} appraisals approved.`, 'success');
        selectedIds.value = [];
        await fetchSubmissions();
    } catch (e) {
        showAlert('Error', 'Some approvals failed.', 'error');
    } finally { loading.value = false; }
};
const exportSelectedCSV = () => {
    const selected = submissions.value.filter(s => selectedIds.value.includes(s.id));
    if (!selected.length) { showAlert('Info', 'No items selected.', 'info'); return; }

    // Use same comprehensive format as bulk export
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
                overflow: visible !important;
            }
            #print-clone .doc-page {
                padding: 10mm !important;
                margin: 0 !important;
                width: 100% !important;
                min-height: auto !important;
                max-height: none !important;
                height: auto !important;
                box-shadow: none !important;
                border: none !important;
                page-break-after: always !important;
                break-after: page !important;
                display: block !important;
                overflow: visible !important;
                border-radius: 0 !important;
            }
            #print-clone .doc-page:last-child {
                page-break-after: avoid !important;
            }
            #print-clone .no-print { display: none !important; }
            #print-clone table { page-break-inside: auto !important; }
            #print-clone tr { page-break-inside: avoid !important; break-inside: avoid !important; }
            #print-clone thead { display: table-header-group !important; }
            #print-clone .space-y-4 > *, #print-clone .space-y-6 > *, #print-clone .space-y-8 > *, #print-clone .space-y-10 > * {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            #print-clone .grid { page-break-inside: avoid !important; break-inside: avoid !important; }
            #print-clone .mt-auto { margin-top: 10mm !important; }
            #print-clone .watermark-logo {
                position: absolute !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                width: 280px !important;
                opacity: 0.04 !important;
                pointer-events: none !important;
                z-index: 0 !important;
                display: block !important;
            }
            @page { size: A4 portrait; margin: 8mm 5mm; }
        }
        @media screen {
            #print-clone { display: none !important; }
        }
    `;
    document.head.appendChild(printStyle);

    setTimeout(() => {
        window.print();
        setTimeout(() => {
            document.body.removeChild(printContainer);
            document.head.removeChild(printStyle);
        }, 500);
    }, 300);
};

const getDossierId = (goal) => {
    if (!goal) return 'PMS-000';
    const deptPrefix = goal.department ? goal.department.substring(0, 3).toUpperCase() : (goal.user?.department ? goal.user.department.substring(0, 3).toUpperCase() : 'PMS');
    return `${deptPrefix}-${goal.employee_code || goal.user?.employee_code || goal.id}`;
};
</script>

<template>
    <div class="submissions-wrapper min-h-screen">
        <div v-if="viewMode === 'list'">

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight" style="color: var(--text-color, #1e293b);">Submissions List</h1>
                    <p class="text-sm mt-0.5" style="color: var(--text-secondary, #94a3b8);">View and print submitted employee appraisals</p>
                </div>
                <div class="flex items-center gap-2 mt-3 md:mt-0">
                    <button @click="exportSubmissionsCSV"
                        class="px-4 py-2.5 bg-indigo-600 text-white rounded-xl text-xs font-semibold flex items-center gap-2 transition-all hover:bg-indigo-700 active:scale-95 border-none cursor-pointer shadow-sm">
                        <i class="pi pi-file-excel text-xs"></i> Export CSV
                    </button>
                </div>
            </div>

            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="rounded-xl p-5 border flex items-center gap-4" style="background: var(--surface-card, #fff); border-color: var(--border-color, #e2e8f0);">
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 flex items-center justify-center"><i class="pi pi-inbox text-indigo-600 text-lg"></i></div>
                    <div>
                        <p class="text-2xl font-bold" style="color: var(--text-color, #1e293b);">{{ stats.total }}</p>
                        <p class="text-[10px] font-medium uppercase tracking-wider" style="color: var(--text-secondary, #94a3b8);">Total Submissions</p>
                    </div>
                </div>
                <div class="rounded-xl p-5 border flex items-center gap-4" style="background: var(--surface-card, #fff); border-color: var(--border-color, #e2e8f0);">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center"><i class="pi pi-check-circle text-emerald-600 text-lg"></i></div>
                    <div>
                        <p class="text-2xl font-bold text-emerald-600">{{ stats.rated }}</p>
                        <p class="text-[10px] font-medium uppercase tracking-wider" style="color: var(--text-secondary, #94a3b8);">Rated</p>
                    </div>
                </div>
                <div class="rounded-xl p-5 border flex items-center gap-4" style="background: var(--surface-card, #fff); border-color: var(--border-color, #e2e8f0);">
                    <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center"><i class="pi pi-star text-amber-500 text-lg"></i></div>
                    <div>
                        <p class="text-2xl font-bold text-amber-600">{{ stats.avgRating }}</p>
                        <p class="text-[10px] font-medium uppercase tracking-wider" style="color: var(--text-secondary, #94a3b8);">Avg Rating</p>
                    </div>
                </div>
            </div>

            <!-- (Bulk actions removed — this page is view/print only) -->

            <!-- Main Table Card -->
            <div class="rounded-2xl border overflow-hidden shadow-sm" style="background: var(--surface-card, #fff); border-color: var(--border-color, #e2e8f0);">

                <!-- Filter Bar inside card -->
                <div class="px-6 py-4 flex flex-col md:flex-row items-center justify-between gap-3" style="border-bottom: 1px solid var(--border-color, #e2e8f0);">
                    <div class="flex items-center gap-2 w-full md:w-auto flex-1">
                        <div class="relative flex-1 md:max-w-xs">
                            <input v-model="searchQuery" type="text" placeholder="Search..."
                                class="w-full pl-9 pr-4 py-2 rounded-lg text-xs border focus:ring-2 focus:ring-indigo-100 transition-all" style="background: var(--surface-card, #fff); color: var(--text-color, #334155); border-color: var(--border-color, #e2e8f0);" />
                            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs" style="color: var(--text-secondary, #94a3b8);"></i>
                        </div>
                        <select v-model="filterDepartment" class="appearance-none pl-3 pr-8 py-2 border rounded-lg text-xs cursor-pointer" style="background: var(--surface-card, #fff); color: var(--text-color, #475569); border-color: var(--border-color, #e2e8f0);">
                            <option value="">All Depts</option>
                            <option v-for="dept in depts" :key="dept.id" :value="dept.name">{{ dept.name }}</option>
                        </select>
                        <select v-model="filterLocation" class="appearance-none pl-3 pr-8 py-2 border rounded-lg text-xs cursor-pointer" style="background: var(--surface-card, #fff); color: var(--text-color, #475569); border-color: var(--border-color, #e2e8f0);">
                            <option value="">All Locations</option>
                            <option v-for="loc in branchs" :key="loc.id" :value="loc.name">{{ loc.name }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2 text-[10px] font-semibold" style="color: var(--text-secondary, #94a3b8);">
                        <i class="pi pi-list text-xs"></i>
                        {{ filteredSubmissions.length }} records
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="flex flex-col items-center justify-center py-20">
                    <div class="w-10 h-10 border-3 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="mt-3 text-sm" style="color: var(--text-secondary, #94a3b8);">Loading submissions...</p>
                </div>

                <!-- Empty State -->
                <div v-else-if="filteredSubmissions.length === 0" class="flex flex-col items-center justify-center py-16">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-3" style="background: var(--surface-ground, #f8fafc);">
                        <i class="pi pi-folder-open text-2xl" style="color: var(--text-secondary, #cbd5e1);"></i>
                    </div>
                    <h3 class="text-base font-semibold" style="color: var(--text-color, #334155);">No submissions found</h3>
                    <p class="text-xs mt-1" style="color: var(--text-secondary, #94a3b8);">Try adjusting your filters or cycle year.</p>
                </div>

                <!-- Invoice-Style Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color, #e2e8f0);">
                                <th class="px-6 py-3 text-[11px] font-semibold uppercase tracking-wider" style="color: var(--text-secondary, #94a3b8);">Employee</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider" style="color: var(--text-secondary, #94a3b8);">Record ID</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider" style="color: var(--text-secondary, #94a3b8);">Manager</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider" style="color: var(--text-secondary, #94a3b8);">Date</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-center" style="color: var(--text-secondary, #94a3b8);">Rating</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-right" style="color: var(--text-secondary, #94a3b8);">Status</th>
                                <th class="px-6 py-3 text-[11px] font-semibold uppercase tracking-wider text-right" style="color: var(--text-secondary, #94a3b8);">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="appraisal in filteredSubmissions" :key="appraisal.id">
                            <tr class="group transition-colors duration-150 cursor-pointer" style="border-bottom: 1px solid var(--border-color, #f1f5f9);"
                                @mouseenter="$event.currentTarget.style.background='var(--surface-ground, #f8fafc)'" @mouseleave="$event.currentTarget.style.background='transparent'">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img :src="'https://ui-avatars.com/api/?name='+encodeURIComponent(appraisal.candidate_name || appraisal.user?.name || 'U')+'&background=6366f1&color=fff&size=80&bold=true'" class="w-9 h-9 rounded-full object-cover ring-2 ring-white shadow-sm" />
                                        <div>
                                            <span class="text-[13px] font-semibold block" style="color: var(--text-color, #1e293b);">{{ appraisal.candidate_name || appraisal.user?.name || 'Staff' }}</span>
                                            <span class="text-[11px] block mt-0.5" style="color: var(--text-secondary, #94a3b8);">{{ appraisal.title || 'Goal Setting' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="text-xs font-medium" style="color: var(--text-color, #475569);">{{ getRecordId(appraisal) }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="text-xs" style="color: var(--text-secondary, #64748b);">{{ appraisal.user?.name || appraisal.manager_signature_name || 'N/A' }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <i class="pi pi-calendar text-[10px]" style="color: var(--text-secondary, #cbd5e1);"></i>
                                        <span class="text-xs" style="color: var(--text-secondary, #64748b);">{{ formatDate(appraisal.submitted_at || appraisal.created_at) }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-sm font-bold" style="color: var(--text-color, #1e293b);">{{ calculateAverageRating(appraisal) }}</span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-600">
                                        Submitted
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button @click.stop="openActionModal(appraisal)" class="w-8 h-8 rounded-lg flex items-center justify-center border transition-all hover:bg-indigo-50 hover:border-indigo-200 hover:text-indigo-600 opacity-0 group-hover:opacity-100" style="border-color: var(--border-color, #e2e8f0); color: var(--text-secondary, #94a3b8);" title="Preview">
                                        <i class="pi pi-eye text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- End viewMode === 'list' -->

        <!-- Dossier-Style Full-Screen Preview Dialog -->
        <Dialog v-model:visible="showActionModal" :modal="true" :showHeader="false"
            class="!p-0 overflow-hidden shadow-2xl border-none"
            :style="{ width: '100vw', height: '100vh', maxWidth: '100vw', maxHeight: '100vh', margin: '0' }"
            :contentStyle="{ padding: '0', backgroundColor: '#F1F5F9', display: 'flex' }">

            <div v-if="selectedAppraisal" class="flex w-full h-full overflow-hidden">

                <!-- Document Viewport (Left/Center) -->
                <div class="flex-1 overflow-y-auto bg-slate-200 p-8 md:p-12 lg:p-20 flex flex-col items-center custom-scrollbar scroll-smooth">

                    <div id="protocol-report" class="w-full max-w-[210mm] space-y-8 print:m-0 print:shadow-none print:w-full no-scrollbar">

                        <!-- Page 1: Profile & Definitions -->
                        <div class="doc-page bg-white shadow-2xl rounded-sm p-12 md:p-16 w-[210mm] min-h-[297mm] flex flex-col relative">
                            <img :src="melcomLogo" class="watermark-logo" alt="" />
                            <!-- Header -->
                            <div class="flex justify-between items-start mb-12 border-b-2 border-slate-900 pb-8">
                                <div>
                                    <div class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.3em] mb-3">MELCOM HR PMS</div>
                                    <h1 class="text-4xl font-black text-slate-900 tracking-tighter uppercase leading-tight">{{ selectedAppraisal.candidate_name || selectedAppraisal.user?.name || 'Employee' }}</h1>
                                    <div class="flex items-center gap-3 mt-4">
                                        <span class="px-3 py-1 bg-slate-100 text-slate-600 text-[9px] font-black uppercase rounded">{{ selectedAppraisal.user?.employee_code || selectedAppraisal.employee_code || 'EMP-XXXX' }}</span>
                                        <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                        <span class="text-[10px] font-bold text-slate-400 capitalize">{{ selectedAppraisal.user?.department || selectedAppraisal.department || 'N/A' }} &#x2022; {{ selectedAppraisal.user?.location || 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Fiscal Cycle</div>
                                    <div class="text-2xl font-black text-slate-900">{{ selectedAppraisal.year }}</div>
                                    <div class="text-[9px] font-bold text-slate-400 mt-1">Created: {{ formatDate(selectedAppraisal.created_at) }}</div>
                                </div>
                            </div>

                            <!-- Section A: Strategic Definition -->
                            <div class="space-y-10">
                                <div>
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="flex-1 h-[1px] bg-slate-200"></div>
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">YEARLY SMART GOALS SETTING</span>
                                        <div class="flex-1 h-[1px] bg-slate-200"></div>
                                    </div>
                                    <div class="space-y-6">
                                        <div>
                                            <h3 class="text-xs font-black text-slate-900 uppercase mb-2">Primary Goal Title</h3>
                                            <p class="text-base font-black text-indigo-700 uppercase leading-snug">{{ selectedAppraisal.title }}</p>
                                        </div>
                                        <div class="grid grid-cols-3 gap-6">
                                            <div>
                                                <h3 class="text-[10px] font-black text-slate-400 uppercase mb-2">Category</h3>
                                                <p class="text-xs font-bold text-slate-700 uppercase">{{ selectedAppraisal.category || 'General' }}</p>
                                            </div>
                                            <div>
                                                <h3 class="text-[10px] font-black text-slate-400 uppercase mb-2">Target</h3>
                                                <p class="text-xs font-black text-slate-900 uppercase">{{ selectedAppraisal.target || '100' }}%</p>
                                            </div>
                                            <div>
                                                <h3 class="text-[10px] font-black text-slate-400 uppercase mb-2">Line Manager</h3>
                                                <p class="text-xs font-black text-slate-900 uppercase">{{ selectedAppraisal.manager_name || 'Administrator' }}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <h3 class="text-[10px] font-black text-slate-400 uppercase mb-3">Narrative Description & Objectives</h3>
                                            <div class="space-y-3">
                                                <div v-for="(desc, index) in parseList(selectedAppraisal.description)" :key="index" class="p-4 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold text-slate-600 leading-relaxed">
                                                    <span class="text-indigo-600 mr-2 opacity-50">{{ (index+1).toString().padStart(2, '0') }}</span> {{ desc }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="flex-1 h-[1px] bg-slate-200"></div>
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">PURPOSE & VALUE</span>
                                        <div class="flex-1 h-[1px] bg-slate-200"></div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4">
                                        <div v-for="(p, pIdx) in parseList(selectedAppraisal.purposes)" :key="pIdx" class="flex gap-4 p-4 bg-white border border-slate-100 rounded-xl shadow-sm">
                                            <div class="w-2 h-2 rounded-full bg-teal-500 mt-1.5 shrink-0"></div>
                                            <p class="text-[11px] font-bold text-slate-600 leading-normal">{{ p }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Challenges -->
                                <div v-if="parseList(selectedAppraisal.challenges).filter(c => c && c.trim && c.trim()).length">
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="flex-1 h-[1px] bg-slate-200"></div>
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">CHALLENGES & RISKS</span>
                                        <div class="flex-1 h-[1px] bg-slate-200"></div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4">
                                        <div v-for="(c, cIdx) in parseList(selectedAppraisal.challenges)" :key="cIdx" class="flex gap-4 p-4 bg-red-50/50 border border-red-100 rounded-xl">
                                            <div class="w-2 h-2 rounded-full bg-red-400 mt-1.5 shrink-0"></div>
                                            <p class="text-[11px] font-bold text-slate-600 leading-normal">{{ c }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- SMART Criteria -->
                                <div v-if="selectedAppraisal.smart_criteria">
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="flex-1 h-[1px] bg-slate-200"></div>
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">SMART CRITERIA CHECKLIST</span>
                                        <div class="flex-1 h-[1px] bg-slate-200"></div>
                                    </div>
                                    <table class="w-full text-left text-xs border-collapse">
                                        <thead class="bg-slate-900 text-white text-[9px] font-black uppercase tracking-widest">
                                            <tr>
                                                <th class="px-5 py-3 rounded-tl-xl">My Goal Is...</th>
                                                <th class="px-5 py-3 text-center rounded-tr-xl w-20">Check</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white border-x border-b border-slate-100">
                                            <tr v-for="(item, key) in { specific: { letter: 'S', label: 'Specific', color: 'bg-indigo-600', desc: 'Clearly defines what needs to be accomplished' }, measurable: { letter: 'M', label: 'Measurable', color: 'bg-purple-600', desc: 'Has quantifiable indicators of progress' }, attainable: { letter: 'A', label: 'Attainable', color: 'bg-emerald-600', desc: 'Realistic and achievable with available resources' }, relevant: { letter: 'R', label: 'Relevant', color: 'bg-red-500', desc: 'Aligned with broader business objectives' }, time_bound: { letter: 'T', label: 'Time-Bound', color: 'bg-violet-600', desc: 'Has a clear deadline or timeframe' } }" :key="key">
                                                <td class="px-5 py-4">
                                                    <div class="flex items-center gap-3">
                                                        <span class="w-7 h-7 rounded-lg text-white text-[10px] font-black flex items-center justify-center shrink-0" :class="item.color">{{ item.letter }}</span>
                                                        <div>
                                                            <p class="text-[11px] font-black text-slate-800">{{ item.label }}</p>
                                                            <p class="text-[9px] text-slate-400">{{ item.desc }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-4 text-center">
                                                    <i class="pi text-base" :class="parseSmart(selectedAppraisal.smart_criteria)?.[key] ? 'pi-check-square text-emerald-500' : 'pi-stop text-slate-300'"></i>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-slate-50">
                                            <tr>
                                                <td class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase">{{ Object.values(parseSmart(selectedAppraisal.smart_criteria) || {}).filter(v => v === true).length }}/5 Criteria Met</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <div class="mt-auto pt-10 text-center">
                                <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest">Page 01 // Official Performance Record // RecordId: {{ getDossierId(selectedAppraisal) }}</p>
                            </div>
                        </div>

                        <!-- Page 2: Quarterly Progress -->
                        <div class="doc-page bg-white shadow-2xl rounded-sm p-12 md:p-16 w-[210mm] min-h-[297mm] flex flex-col break-before-page relative">
                            <img :src="melcomLogo" class="watermark-logo" alt="" />
                            <div class="flex items-center gap-4 mb-10">
                                <div class="w-10 h-1 bg-indigo-600"></div>
                                <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">Goal Start Date — Key Steps</h2>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Measure (Growth Over Last Year & Quarters) — Keep a log of your progress</p>
                            </div>

                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="bg-slate-900 text-white text-[9px] font-black uppercase tracking-widest">
                                    <tr>
                                        <th class="px-5 py-3 rounded-tl-xl w-[100px]">Date</th>
                                        <th class="px-5 py-3">Target Measure</th>
                                        <th class="px-5 py-3 rounded-tr-xl">Attachments</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white border-x border-b border-slate-100">
                                    <tr v-for="(q, qIdx) in parseList(selectedAppraisal.quarterly_tracking)" :key="qIdx">
                                        <td class="px-5 py-4 align-top">
                                            <span class="inline-block px-2 py-0.5 rounded text-[9px] font-black text-white mb-1" :class="['bg-red-400','bg-amber-500','bg-emerald-500','bg-indigo-500'][qIdx] || 'bg-slate-500'">{{ q.quarter ? q.quarter.toUpperCase() : 'Q'+(qIdx+1) }}</span>
                                            <p class="text-[9px] text-slate-400 font-bold leading-tight">{{ formatDate(q.start_date) }}</p>
                                            <p class="text-[9px] text-slate-400 font-bold leading-tight">{{ formatDate(q.end_date) }}</p>
                                        </td>
                                        <td class="px-5 py-4 align-top">
                                            <div v-for="(m, mIdx) in parseList(q.target_measures)" :key="mIdx" class="flex items-start gap-2 mb-1">
                                                <i class="pi pi-check-circle text-indigo-400 text-[9px] mt-0.5 shrink-0"></i>
                                                <p class="text-[11px] font-bold text-slate-600 leading-snug">{{ m }}</p>
                                            </div>
                                            <p v-if="!parseList(q.target_measures).length" class="text-[10px] text-slate-300 italic">—</p>
                                        </td>
                                        <td class="px-5 py-4 align-top">
                                            <div v-if="q.attachments && q.attachments.length" class="space-y-1">
                                                <span v-for="(att, aIdx) in q.attachments" :key="aIdx" class="block px-2 py-1 bg-slate-50 border border-slate-100 rounded text-[9px] font-bold text-slate-500 truncate">
                                                    <i class="pi pi-paperclip mr-1 text-[8px]"></i>{{ typeof att === 'string' ? att.split('/').pop() : att.name || ('File ' + (aIdx+1)) }}
                                                </span>
                                            </div>
                                            <p v-else class="text-[10px] text-slate-300 italic">—</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="mt-auto pt-10 text-center">
                                <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest">Page 02 // Official Performance Record // RecordId: {{ getDossierId(selectedAppraisal) }}</p>
                            </div>
                        </div>

                        <!-- Page 3: Appraisal & Review -->
                        <div v-if="selectedAppraisal.appraisal_data"
                            class="doc-page bg-white shadow-2xl rounded-sm p-12 md:p-16 w-[210mm] min-h-[297mm] flex flex-col break-before-page relative">
                            <img :src="melcomLogo" class="watermark-logo" alt="" />

                            <div class="flex items-center gap-4 mb-10">
                                <div class="w-10 h-1 bg-indigo-600"></div>
                                <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">Yearly Performance Assessment</h2>
                            </div>

                            <div class="space-y-10">
                                <!-- Competency Matrix with Descriptions -->
                                <div v-if="parseSmart(selectedAppraisal.appraisal_data)?.competencies">
                                    <table class="w-full text-left font-black text-xs border-collapse">
                                        <thead class="bg-slate-900 text-white text-[9px] font-black uppercase tracking-widest">
                                            <tr>
                                                <th class="px-6 py-4 rounded-tl-xl">Assessment Unit</th>
                                                <th class="px-6 py-4 text-center">Weight</th>
                                                <th class="px-6 py-4 text-center">Self</th>
                                                <th class="px-6 py-4 text-center rounded-tr-xl">Mgr</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white border-x border-b border-slate-100 overflow-hidden">
                                            <template v-for="(comp, cIdx) in parseSmart(selectedAppraisal.appraisal_data).competencies" :key="cIdx">
                                                <tr>
                                                    <td class="px-6 pt-5 pb-1 font-black text-slate-800 text-[11px]">{{ comp.title }}</td>
                                                    <td class="px-6 pt-5 pb-1 text-center font-bold text-slate-400">{{ comp.weight }}%</td>
                                                    <td class="px-6 pt-5 pb-1 text-center font-bold text-slate-400">{{ comp.selfRating || '0' }}</td>
                                                    <td class="px-6 pt-5 pb-1 text-center font-black text-indigo-600">{{ comp.managerRating || '0' }}</td>
                                                </tr>
                                                <tr v-if="comp.descriptions && comp.descriptions.length">
                                                    <td colspan="4" class="px-6 pb-4 pt-0">
                                                        <div v-for="(desc, dIdx) in comp.descriptions" :key="dIdx" class="text-[9px] text-slate-400 leading-relaxed pl-2 border-l-2 border-slate-100 mt-1">
                                                            {{ desc }}
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-slate-50 border-b border-slate-100">
                                                <td class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase">Self Assessment Average</td>
                                                <td></td>
                                                <td class="px-6 py-3 text-center text-sm font-bold text-slate-500">{{ getSelfRating(selectedAppraisal) || '0.0' }}/5</td>
                                                <td></td>
                                            </tr>
                                            <tr class="bg-indigo-600">
                                                <td class="px-6 py-5 text-base font-black text-white uppercase tracking-wide rounded-bl-xl">Overall Achievement Score</td>
                                                <td colspan="2"></td>
                                                <td class="px-6 py-5 text-center text-2xl font-black text-white rounded-br-xl">{{ getManagerRating(selectedAppraisal) || '0.0' }}/5</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <!-- Performance Rating & Potential -->
                                <div v-if="parseSmart(selectedAppraisal.appraisal_data)?.performanceRating" class="space-y-6">
                                    <div class="flex items-center gap-4 mb-4">
                                        <div class="flex-1 h-[1px] bg-slate-200"></div>
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">PERFORMANCE ASSESSMENT</span>
                                        <div class="flex-1 h-[1px] bg-slate-200"></div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-8">
                                        <div class="p-5 bg-indigo-50/50 border border-indigo-100 rounded-xl">
                                            <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Performance Rating</h4>
                                            <p class="text-lg font-black text-indigo-600">{{ parseSmart(selectedAppraisal.appraisal_data).performanceRating }}/5</p>
                                        </div>
                                        <div class="p-5 bg-teal-50/50 border border-teal-100 rounded-xl">
                                            <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Potential Rating</h4>
                                            <p class="text-sm font-black text-teal-700">{{ parseSmart(selectedAppraisal.appraisal_data).potentialRating || 'Not assessed' }}</p>
                                        </div>
                                    </div>
                                    <div v-if="parseSmart(selectedAppraisal.appraisal_data).rating_comments && parseSmart(selectedAppraisal.appraisal_data).rating_comments[parseSmart(selectedAppraisal.appraisal_data).performanceRating]" class="p-5 bg-slate-50 border border-slate-100 rounded-xl">
                                        <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Rating Comment</h4>
                                        <p class="text-[11px] font-bold text-slate-600 leading-relaxed">{{ parseSmart(selectedAppraisal.appraisal_data).rating_comments[parseSmart(selectedAppraisal.appraisal_data).performanceRating] }}</p>
                                    </div>
                                </div>

                            </div>

                            <div class="mt-auto pt-10 text-center">
                                <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest">Page 03 // Official Performance Record // RecordId: {{ getDossierId(selectedAppraisal) }}</p>
                            </div>
                        </div>

                        <!-- Page 4: Manager Commentary, Review Summary & Signatures -->
                        <div v-if="selectedAppraisal.appraisal_data"
                            class="doc-page bg-white shadow-2xl rounded-sm p-12 md:p-16 w-[210mm] min-h-[297mm] flex flex-col break-before-page relative">
                            <img :src="melcomLogo" class="watermark-logo" alt="" />

                            <div class="flex items-center gap-4 mb-10">
                                <div class="w-10 h-1 bg-indigo-600"></div>
                                <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">Manager Commentary</h2>
                            </div>

                            <div class="space-y-10">
                                <!-- Manager Commentary: What impressed most/least, general comments -->
                                <div v-if="parseSmart(selectedAppraisal.appraisal_data)?.impressedMost || parseSmart(selectedAppraisal.appraisal_data)?.impressedLeast || parseSmart(selectedAppraisal.appraisal_data)?.comments" class="space-y-4">
                                    <div class="grid grid-cols-1 gap-4">
                                        <div v-if="parseSmart(selectedAppraisal.appraisal_data).impressedMost" class="p-5 bg-emerald-50/50 border border-emerald-100 rounded-xl">
                                            <h4 class="text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-2">What Impressed Most</h4>
                                            <p class="text-[11px] font-bold text-slate-600 leading-relaxed">{{ parseSmart(selectedAppraisal.appraisal_data).impressedMost }}</p>
                                        </div>
                                        <div v-if="parseSmart(selectedAppraisal.appraisal_data).impressedLeast" class="p-5 bg-amber-50/50 border border-amber-100 rounded-xl">
                                            <h4 class="text-[9px] font-black text-amber-600 uppercase tracking-widest mb-2">What Impressed Least</h4>
                                            <p class="text-[11px] font-bold text-slate-600 leading-relaxed">{{ parseSmart(selectedAppraisal.appraisal_data).impressedLeast }}</p>
                                        </div>
                                        <div v-if="parseSmart(selectedAppraisal.appraisal_data).comments" class="p-5 bg-slate-50 border border-slate-100 rounded-xl">
                                            <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">General Comments</h4>
                                            <p class="text-[11px] font-bold text-slate-600 leading-relaxed">{{ parseSmart(selectedAppraisal.appraisal_data).comments }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- End-of-Year Review Summary -->
                                <div v-if="parseSmart(selectedAppraisal.appraisal_data)?.review_summary">
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="flex-1 h-[1px] bg-slate-200"></div>
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">END-OF-YEAR REVIEW (EXECUTIVE SUMMARY)</span>
                                        <div class="flex-1 h-[1px] bg-slate-200"></div>
                                    </div>

                                    <!-- Achievements -->
                                    <div v-if="parseSmart(selectedAppraisal.appraisal_data)?.review_summary?.A" class="mb-6">
                                        <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3">Achievements vs Targets</h4>
                                        <div class="space-y-2">
                                            <div v-for="(item, idx) in parseSmart(selectedAppraisal.appraisal_data).review_summary.A" :key="idx" class="grid grid-cols-2 gap-4 p-4 bg-slate-50 rounded-xl border border-slate-100">
                                                <div>
                                                    <label class="block text-[7px] font-bold text-slate-400 uppercase mb-0.5">Achievement</label>
                                                    <p class="text-[11px] font-semibold text-slate-800">{{ item.achievement || '---' }}</p>
                                                </div>
                                                <div>
                                                    <label class="block text-[7px] font-bold text-slate-400 uppercase mb-0.5">Target</label>
                                                    <p class="text-[11px] text-slate-600">{{ item.target || '---' }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sections B-E -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div v-for="sec in ['B', 'C', 'D', 'E']" :key="sec" class="border border-slate-100 rounded-xl p-4">
                                            <span class="block text-[9px] font-black uppercase tracking-wider mb-2" :class="sec === 'E' ? 'text-indigo-600' : 'text-slate-500'">
                                                {{ sec === 'B' ? 'Initiatives Taken' : sec === 'C' ? 'Next Steps' : sec === 'D' ? 'Areas for Improvement' : 'Manager Observations' }}
                                            </span>
                                            <p v-for="(text, ix) in parseSmart(selectedAppraisal.appraisal_data)?.review_summary?.[sec]" :key="ix" class="text-[11px] text-slate-700 leading-relaxed mb-1.5 last:mb-0">
                                                {{ text || 'No response provided.' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- HOD Comments & Director Remarks -->
                                <div v-if="parseSmart(selectedAppraisal.appraisal_data)?.hod_comments || parseSmart(selectedAppraisal.appraisal_data)?.director_remarks" class="space-y-4">
                                    <div v-if="parseSmart(selectedAppraisal.appraisal_data).hod_comments" class="p-5 bg-slate-50 border border-slate-100 rounded-xl">
                                        <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">HOD Comments</h4>
                                        <p class="text-[11px] font-bold text-slate-600 leading-relaxed">{{ parseSmart(selectedAppraisal.appraisal_data).hod_comments }}</p>
                                    </div>
                                    <div v-if="parseSmart(selectedAppraisal.appraisal_data).director_remarks" class="p-5 bg-slate-50 border border-slate-100 rounded-xl">
                                        <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Director Remarks</h4>
                                        <p class="text-[11px] font-bold text-slate-600 leading-relaxed">{{ parseSmart(selectedAppraisal.appraisal_data).director_remarks }}</p>
                                    </div>
                                </div>

                                <!-- All 4 Signatures -->
                                <div class="grid grid-cols-2 gap-12 pt-8 border-t border-slate-100">
                                    <div>
                                        <h4 class="text-[9px] font-black text-slate-400 uppercase mb-4 tracking-widest">Employee Signature</h4>
                                        <div class="h-16 border-b-2 border-slate-900 mt-2 mb-2 italic text-slate-400 text-[10px] flex items-end pb-2">
                                            {{ parseSmart(selectedAppraisal.appraisal_data)?.candidate_signature_name || selectedAppraisal.candidate_name || selectedAppraisal.user?.name || 'Signature pending' }}
                                        </div>
                                        <p class="text-xs font-black text-slate-900 uppercase tracking-tighter">{{ parseSmart(selectedAppraisal.appraisal_data)?.candidate_signature_name || selectedAppraisal.candidate_name || selectedAppraisal.user?.name || 'Employee' }}</p>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase">{{ formatDate(parseSmart(selectedAppraisal.appraisal_data)?.signature_date) || 'Date' }}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-[9px] font-black text-slate-400 uppercase mb-4 tracking-widest">Line Manager</h4>
                                        <div class="h-16 border-b-2 border-slate-900 mt-2 mb-2 italic text-slate-400 text-[10px] flex items-end pb-2">
                                            {{ parseSmart(selectedAppraisal.appraisal_data)?.manager_signature_name || selectedAppraisal.manager_name || 'Signature pending' }}
                                        </div>
                                        <p class="text-xs font-black text-slate-900 uppercase tracking-tighter">{{ parseSmart(selectedAppraisal.appraisal_data)?.manager_signature_name || selectedAppraisal.manager_name || 'Manager' }}</p>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase">{{ formatDate(parseSmart(selectedAppraisal.appraisal_data)?.signature_date) || 'Date' }}</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-12 pt-6">
                                    <div>
                                        <h4 class="text-[9px] font-black text-slate-400 uppercase mb-4 tracking-widest">HOD / Functional Head</h4>
                                        <div class="h-16 border-b-2 border-slate-900 mt-2 mb-2 italic text-slate-400 text-[10px] flex items-end pb-2">
                                            {{ parseSmart(selectedAppraisal.appraisal_data)?.hod_signature_name || 'Signature pending' }}
                                        </div>
                                        <p class="text-xs font-black text-slate-900 uppercase tracking-tighter">{{ parseSmart(selectedAppraisal.appraisal_data)?.hod_signature_name || 'Designee' }}</p>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase">{{ formatDate(parseSmart(selectedAppraisal.appraisal_data)?.hod_signature_date) || 'Date' }}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-[9px] font-black text-slate-400 uppercase mb-4 tracking-widest">Director / Management</h4>
                                        <div class="h-16 border-b-2 border-slate-900 mt-2 mb-2 italic text-slate-400 text-[10px] flex items-end pb-2">
                                            {{ parseSmart(selectedAppraisal.appraisal_data)?.director_signature_name || 'Signature pending' }}
                                        </div>
                                        <p class="text-xs font-black text-slate-900 uppercase tracking-tighter">{{ parseSmart(selectedAppraisal.appraisal_data)?.director_signature_name || 'Director' }}</p>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase">{{ formatDate(parseSmart(selectedAppraisal.appraisal_data)?.director_signature_date) || 'Date' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto pt-10 text-center">
                                <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest">Page 04 // Official Performance Record // RecordId: {{ getDossierId(selectedAppraisal) }}</p>
                            </div>
                        </div>

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
                                <span class="text-[10px] font-black uppercase text-emerald-400">Submitted</span>
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

        <!-- Standalone fallback removed: PDF download now opens modal -->
    </div> <!-- End submissions-wrapper 355 -->
</template>

<style scoped>
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
.submissions-container {
    padding-bottom: 2rem;
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
  /* Hide sidebar, navigation, and non-report elements */
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

  /* Make all ancestors of the report visible and flow naturally */
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

  /* Boost contrast for paper */
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

/* Dark mode status pills */
:global(body.dark-mode) .text-emerald-600.bg-emerald-50 { background: rgba(16, 185, 129, 0.15) !important; }
:global(body.dark-mode) .text-amber-600.bg-amber-50 { background: rgba(245, 158, 11, 0.15) !important; }
:global(body.dark-mode) .text-rose-600.bg-rose-50 { background: rgba(244, 63, 94, 0.15) !important; }

/* Slide-down transition for bulk action bar */
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
