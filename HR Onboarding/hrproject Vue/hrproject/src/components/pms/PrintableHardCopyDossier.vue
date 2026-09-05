<template>
    <div class="hardcopy-dossier-root">
        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- PAGE 1: YEARLY SMART GOALS SETTING & QUARTERLY PROGRESS        -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <div class="hardcopy-page" id="hardcopy-page-1">
            <!-- Header Banner -->
            <div class="header-banner banner-grey">
                YEARLY SMART GOALS SETTING
            </div>

            <!-- Top Metadata Table -->
            <table class="form-table meta-table">
                <tbody>
                    <tr>
                        <td class="cell-label" style="width: 18%;">Candidate Name:-</td>
                        <td class="cell-value" style="width: 32%;">
                            <strong>{{ norm.candidateName }}</strong>
                        </td>
                        <td class="cell-label" style="width: 18%;">Line Manager Name:-</td>
                        <td class="cell-value" style="width: 32%;">
                            <strong>{{ norm.lineManagerName }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="cell-label">Signature:-</td>
                        <td class="cell-value">
                            <span class="sig-font">{{ norm.candidateSignature || norm.candidateName }}</span>
                            <span class="sig-date" v-if="norm.candidateSignatureDate">({{ formatDate(norm.candidateSignatureDate) }})</span>
                        </td>
                        <td class="cell-label">Signature:-</td>
                        <td class="cell-value">
                            <span class="sig-font">{{ norm.managerSignature || norm.lineManagerName }}</span>
                            <span class="sig-date" v-if="norm.managerSignatureDate">({{ formatDate(norm.managerSignatureDate) }})</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="cell-label">Job Title:-</td>
                        <td class="cell-value">{{ norm.jobTitle }}</td>
                        <td class="cell-label">Job Title:-</td>
                        <td class="cell-value">{{ norm.lineManagerTitle }}</td>
                    </tr>
                    <tr>
                        <td class="cell-label">Emp Code / Dept:</td>
                        <td class="cell-value">{{ norm.employeeCode }} &bull; {{ norm.department }} ({{ norm.location }})</td>
                        <td class="cell-label">Assessment Year:</td>
                        <td class="cell-value"><strong>{{ norm.year }}</strong> (Created: {{ formatDate(norm.createdDate) }})</td>
                    </tr>
                </tbody>
            </table>

            <!-- Main Body: Goals, Purposes, Challenges (Left) & SMART Checklist (Right) -->
            <div class="goals-smart-split">
                <!-- Left Column: Goals, Purposes, Challenges -->
                <div class="goals-narrative-col">
                    <!-- Goals Section -->
                    <div class="section-box">
                        <div class="section-banner banner-lavender">
                            <span class="sec-title">GOALS</span>
                            <span class="sec-subtitle">Be specific and concise. Include the measure and time frame.</span>
                        </div>
                        <div class="section-body">
                            <div class="primary-goal-row">
                                <span class="badge-cat">{{ norm.category }}</span>
                                <strong class="goal-title-text">{{ norm.title }}</strong>
                                <span class="target-chip">Target: {{ norm.target }}%</span>
                            </div>
                            <ol class="numbered-list" v-if="norm.descriptions.length">
                                <li v-for="(desc, dIdx) in norm.descriptions" :key="dIdx">
                                    {{ desc }}
                                </li>
                            </ol>
                            <p v-else class="empty-text">No narrative objectives specified.</p>
                        </div>
                    </div>

                    <!-- Purposes Section -->
                    <div class="section-box">
                        <div class="section-banner banner-iceblue">
                            <span class="sec-title">PURPOSES</span>
                            <span class="sec-subtitle">Why is the goal relevant? What are the benefits?</span>
                        </div>
                        <div class="section-body">
                            <ul class="bullet-list" v-if="norm.purposes.length">
                                <li v-for="(p, pIdx) in norm.purposes" :key="pIdx">
                                    {{ p }}
                                </li>
                            </ul>
                            <p v-else class="empty-text">No purpose statements specified.</p>
                        </div>
                    </div>

                    <!-- Challenges Section -->
                    <div class="section-box">
                        <div class="section-banner banner-iceblue">
                            <span class="sec-title">CHALLENGES</span>
                            <span class="sec-subtitle">What are the challenges to overcome? What resources and skills are needed?</span>
                        </div>
                        <div class="section-body">
                            <ul class="bullet-list" v-if="norm.challenges.length">
                                <li v-for="(c, cIdx) in norm.challenges" :key="cIdx">
                                    {{ c }}
                                </li>
                            </ul>
                            <p v-else class="empty-text">No challenges recorded.</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: SMART Table & Completion Date -->
                <div class="smart-table-col">
                    <table class="form-table smart-table">
                        <thead>
                            <tr class="header-row-dark">
                                <th style="width: 70%;">MY GOAL IS...</th>
                                <th style="width: 30%; text-align: center;">Check (&check;)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="smart-name">Specific</td>
                                <td class="smart-check cell-s" :class="{ 'checked': norm.smartCriteria.specific }">
                                    <span class="smart-letter">S</span>
                                    <span v-if="norm.smartCriteria.specific" class="checkmark-symbol">&#10003;</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="smart-name">Measurable</td>
                                <td class="smart-check cell-m" :class="{ 'checked': norm.smartCriteria.measurable }">
                                    <span class="smart-letter">M</span>
                                    <span v-if="norm.smartCriteria.measurable" class="checkmark-symbol">&#10003;</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="smart-name">Attainable</td>
                                <td class="smart-check cell-a" :class="{ 'checked': norm.smartCriteria.attainable }">
                                    <span class="smart-letter">A</span>
                                    <span v-if="norm.smartCriteria.attainable" class="checkmark-symbol">&#10003;</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="smart-name">Relevant</td>
                                <td class="smart-check cell-r" :class="{ 'checked': norm.smartCriteria.relevant }">
                                    <span class="smart-letter">R</span>
                                    <span v-if="norm.smartCriteria.relevant" class="checkmark-symbol">&#10003;</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="smart-name">Time-bound</td>
                                <td class="smart-check cell-t" :class="{ 'checked': norm.smartCriteria.time_bound }">
                                    <span class="smart-letter">T</span>
                                    <span v-if="norm.smartCriteria.time_bound" class="checkmark-symbol">&#10003;</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Completion Date Box -->
                    <div class="completion-date-box">
                        <div class="completion-header">COMPLETION DATE</div>
                        <div class="completion-val">
                            {{ formatDate(norm.targetDate) }}
                        </div>
                    </div>

                    <!-- Criteria Count -->
                    <div class="criteria-score-tag">
                        {{ smartCount }} / 5 Criteria Met
                    </div>
                </div>
            </div>

            <!-- Lower Section: Quarterly Progress Tracking (Key Steps) -->
            <div class="quarterly-section">
                <div class="section-banner banner-sage">
                    <span class="sec-title">GOAL START DATE - KEY STEPS</span>
                    <span class="sec-subtitle">MEASURE (Growth Over Last Year &amp; Quarters)</span>
                </div>

                <table class="form-table quarterly-table">
                    <thead>
                        <tr>
                            <th class="q-head q1-banner" style="width: 25%;">Q1</th>
                            <th class="q-head q2-banner" style="width: 25%;">Q2</th>
                            <th class="q-head q3-banner" style="width: 25%;">Q3</th>
                            <th class="q-head q4-banner" style="width: 25%;">Q4</th>
                        </tr>
                        <tr class="q-subhead-row">
                            <th class="q-subhead">Date / Target / Evidence</th>
                            <th class="q-subhead">Date / Target / Evidence</th>
                            <th class="q-subhead">Date / Target / Evidence</th>
                            <th class="q-subhead">Date / Target / Evidence</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td v-for="(q, qIdx) in norm.quarterly" :key="qIdx" class="q-cell align-top">
                                <div class="q-date-range">
                                    <strong>{{ q.quarter || ('Q' + (qIdx + 1)) }}</strong>:
                                    {{ formatDate(q.start_date) }} &ndash; {{ formatDate(q.end_date) }}
                                </div>
                                <div class="q-measures-block">
                                    <div class="q-sub-title">Target Measure:</div>
                                    <ul class="q-measure-list" v-if="q.target_measures && q.target_measures.length">
                                        <li v-for="(m, mIdx) in q.target_measures" :key="mIdx">{{ m }}</li>
                                    </ul>
                                    <span v-else class="empty-text">No target measures</span>
                                </div>
                                <div class="q-evidence-block">
                                    <div class="q-sub-title">Attachments / Evidence:</div>
                                    <div v-if="q.attachments && q.attachments.length" class="q-evidence-list">
                                        <span v-for="(att, aIdx) in q.attachments" :key="aIdx" class="evidence-tag">
                                            &bull; {{ typeof att === 'string' ? att.split('/').pop() : att.name || ('File ' + (aIdx + 1)) }}
                                        </span>
                                    </div>
                                    <span v-else class="empty-text">None</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Page 1 Footer -->
            <div class="page-footer">
                <span>Page 01 &bull; Yearly SMART Goals Setting</span>
                <span>Melcom HR Performance Management System</span>
                <span>Record ID: {{ dossierId }}</span>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- PAGE 2: YEARLY PERFORMANCE ASSESSMENT                          -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <div class="hardcopy-page" id="hardcopy-page-2">
            <!-- Header Banner -->
            <div class="header-banner banner-blue">
                <em>YEARLY PERFORMANCE ASSESSMENT</em>
            </div>

            <!-- Candidate Info Grid -->
            <table class="form-table meta-table">
                <tbody>
                    <tr>
                        <td class="cell-label" style="width: 20%;">Candidate Name:</td>
                        <td class="cell-value" style="width: 30%;"><strong>{{ norm.candidateName }}</strong></td>
                        <td class="cell-label" style="width: 20%;">Assessment Date:</td>
                        <td class="cell-value" style="width: 30%;"><strong>{{ formatDate(norm.appraisalDate) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="cell-label">Emp. Code:</td>
                        <td class="cell-value"><strong>{{ norm.employeeCode }}</strong></td>
                        <td class="cell-label">Line Manager:</td>
                        <td class="cell-value"><strong>{{ norm.lineManagerName }}</strong></td>
                    </tr>
                    <tr>
                        <td class="cell-label">Job Title:</td>
                        <td class="cell-value">{{ norm.jobTitle }}</td>
                        <td class="cell-label">Overall Final Rating:</td>
                        <td class="cell-value">
                            <span class="rating-highlight-blue">
                                {{ norm.managerOverallScore.toFixed(2) }} / 5.00 ({{ norm.managerOverallPercentage }}%)
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="cell-label">Department / Location:</td>
                        <td class="cell-value" colspan="3">{{ norm.department }} &bull; {{ norm.location }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Competency Table -->
            <table class="form-table comp-table">
                <thead>
                    <tr class="header-row-lightblue">
                        <th style="width: 58%; text-align: left;">
                            <div class="comp-head-title">Performance Key Competencies</div>
                            <div class="comp-head-sub">(Rate on how well they worked during the whole year)</div>
                        </th>
                        <th style="width: 10%; text-align: center;">Wts.</th>
                        <th style="width: 10%; text-align: center;">Self Rating</th>
                        <th style="width: 11%; text-align: center;">Line Manager 1-5 Rating<br><span style="font-size: 8px; font-weight: normal;">(5 Highest)</span></th>
                        <th style="width: 11%; text-align: center;">Line Manager's Weighted Score</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(comp, cIdx) in norm.competencies" :key="cIdx" class="comp-row">
                        <td class="comp-desc-cell">
                            <div class="comp-row-title"><strong>#{{ cIdx + 1 }} {{ comp.title }}</strong></div>
                            <div v-if="comp.descriptions && comp.descriptions.length" class="comp-criteria-list">
                                <div v-for="(d, dIdx) in comp.descriptions" :key="dIdx" class="comp-crit-item">
                                    {{ d }}
                                </div>
                            </div>
                        </td>
                        <td class="cell-center font-bold text-blue">{{ comp.weight }}%</td>
                        <td class="cell-center font-bold">{{ comp.selfRating || '0' }}</td>
                        <td class="cell-center font-black text-darkblue">{{ comp.managerRating || '0' }}</td>
                        <td class="cell-center font-black">{{ getCompWeightedScore(comp) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="comp-summary-row">
                        <td class="comp-summary-label">
                            <strong>Comments:</strong> OVERALL PERFORMANCE RATING
                        </td>
                        <td class="cell-center font-black text-blue">{{ totalWeight }}%</td>
                        <td class="cell-center font-bold">{{ norm.selfAvgScore.toFixed(2) }}</td>
                        <td class="cell-center font-black text-darkblue">{{ norm.managerOverallScore.toFixed(2) }}</td>
                        <td class="cell-center font-black rating-final-cell">{{ norm.managerOverallScore.toFixed(2) }}</td>
                    </tr>
                </tfoot>
            </table>

            <!-- Split Feedback Box: What Impressed Most vs Least -->
            <div class="feedback-split-box">
                <div class="feedback-col">
                    <div class="feedback-col-header">What impressed you the most?</div>
                    <div class="feedback-col-body">
                        {{ norm.impressedMost || 'No specific comments provided.' }}
                    </div>
                </div>
                <div class="feedback-col">
                    <div class="feedback-col-header">What impressed you the least? ( Area of improvement )</div>
                    <div class="feedback-col-body">
                        {{ norm.impressedLeast || 'No specific areas recorded.' }}
                    </div>
                </div>
            </div>

            <!-- Standard 1-5 Performance Rating Guide Table -->
            <div class="scale-guide-wrapper">
                <table class="form-table scale-table">
                    <thead>
                        <tr class="header-row-dark">
                            <th style="width: 32%;">1-5 Rating (5 Highest)</th>
                            <th style="width: 34%;">Performance Rating</th>
                            <th style="width: 34%;">Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr :class="{ 'active-rating-row': (norm.performanceRating || Math.round(norm.managerOverallScore)) === 5 }">
                            <td class="scale-cell-band font-bold">5 - Out Standing / Exceptional</td>
                            <td>High Potential</td>
                            <td>{{ norm.ratingComments[5] || '' }}</td>
                        </tr>
                        <tr :class="{ 'active-rating-row': (norm.performanceRating || Math.round(norm.managerOverallScore)) === 4 }">
                            <td class="scale-cell-band font-bold">4 - Exceeding Expectations</td>
                            <td>High Potential</td>
                            <td>{{ norm.ratingComments[4] || '' }}</td>
                        </tr>
                        <tr :class="{ 'active-rating-row': (norm.performanceRating || Math.round(norm.managerOverallScore)) === 3 }">
                            <td class="scale-cell-band font-bold">3 - Meeting Expectations</td>
                            <td>Good Potential</td>
                            <td>{{ norm.ratingComments[3] || '' }}</td>
                        </tr>
                        <tr :class="{ 'active-rating-row': (norm.performanceRating || Math.round(norm.managerOverallScore)) === 2 }">
                            <td class="scale-cell-band font-bold">2 - Partly Meeting Expectations</td>
                            <td>Low Potential</td>
                            <td>{{ norm.ratingComments[2] || '' }}</td>
                        </tr>
                        <tr :class="{ 'active-rating-row': (norm.performanceRating || Math.round(norm.managerOverallScore)) === 1 }">
                            <td class="scale-cell-band font-bold">1 - Below Expectations / Unsatisfactory</td>
                            <td>Below Potential</td>
                            <td>{{ norm.ratingComments[1] || '' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Page 2 Footer -->
            <div class="page-footer">
                <span>Page 02 &bull; Yearly Performance Assessment</span>
                <span>Melcom HR Performance Management System</span>
                <span>Overall: {{ norm.managerOverallScore.toFixed(2) }} / 5.00 ({{ norm.managerOverallPercentage }}%)</span>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- PAGE 3: END-OF-YEAR REVIEW (EXECUTIVE SUMMARY) & AUTHORIZATION -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <div class="hardcopy-page" id="hardcopy-page-3">
            <!-- Header Title -->
            <div class="review-main-header">
                End-Of-Year Review ( Executive Summary )
            </div>

            <!-- Review Metadata Bar (Cyan Header) -->
            <table class="form-table review-meta-bar">
                <thead>
                    <tr class="header-cyan">
                        <th style="width: 15%;">Emp. Code</th>
                        <th style="width: 30%;">Name</th>
                        <th style="width: 25%;">Division</th>
                        <th style="width: 18%;">Position</th>
                        <th style="width: 12%;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="review-meta-vals">
                        <td class="cell-center"><strong>{{ norm.employeeCode }}</strong></td>
                        <td><strong>{{ norm.candidateName }}</strong></td>
                        <td>{{ norm.department }}</td>
                        <td>{{ norm.jobTitle }}</td>
                        <td class="cell-center">{{ formatDate(norm.reviewDate) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Section A: Goal Achievement vs Target (Pink Banner) -->
            <div class="review-section-block">
                <div class="review-section-banner banner-pink">
                    <span class="sec-letter">A</span>
                    <span class="sec-heading">Goal Achievement vs. Target</span>
                </div>
                <table class="form-table review-data-table">
                    <thead>
                        <tr class="subhead-row">
                            <th style="width: 6%; text-align: center;">#</th>
                            <th style="width: 47%;">Achievement Details</th>
                            <th style="width: 47%;">Target Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, idx) in norm.summaryA" :key="'A'+idx">
                            <td class="cell-center font-bold row-index-cell">{{ idx + 1 }}</td>
                            <td class="cell-text">{{ item.achievement || '---' }}</td>
                            <td class="cell-text">{{ item.target || '---' }}</td>
                        </tr>
                        <tr v-if="!norm.summaryA.length">
                            <td class="cell-center">1</td>
                            <td class="empty-text">No achievement recorded</td>
                            <td class="empty-text">No target recorded</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Section B: Additional Responsibility (Light Green Banner) -->
            <div class="review-section-block">
                <div class="review-section-banner banner-lightgreen">
                    <span class="sec-letter">B</span>
                    <span class="sec-heading">Additional Responsibility / Initiatives Taken</span>
                </div>
                <table class="form-table review-data-table">
                    <tbody>
                        <tr v-for="(item, idx) in norm.summaryB" :key="'B'+idx">
                            <td class="cell-center font-bold row-index-cell" style="width: 6%;">{{ idx + 1 }}</td>
                            <td class="cell-text" style="width: 94%;">{{ item || '---' }}</td>
                        </tr>
                        <tr v-if="!norm.summaryB.length">
                            <td class="cell-center" style="width: 6%;">1</td>
                            <td class="empty-text" style="width: 94%;">No additional initiatives recorded</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Section C: Next Step for Business (Lavender Banner) -->
            <div class="review-section-block">
                <div class="review-section-banner banner-purple">
                    <span class="sec-letter">C</span>
                    <span class="sec-heading">Next Step for Business and what you have planned to meet them</span>
                </div>
                <table class="form-table review-data-table">
                    <tbody>
                        <tr v-for="(item, idx) in norm.summaryC" :key="'C'+idx">
                            <td class="cell-center font-bold row-index-cell" style="width: 6%;">{{ idx + 1 }}</td>
                            <td class="cell-text" style="width: 94%;">{{ item || '---' }}</td>
                        </tr>
                        <tr v-if="!norm.summaryC.length">
                            <td class="cell-center" style="width: 6%;">1</td>
                            <td class="empty-text" style="width: 94%;">No next steps recorded</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Section D: Area of Improvement (Medium Green Banner) -->
            <div class="review-section-block">
                <div class="review-section-banner banner-green">
                    <span class="sec-letter">D</span>
                    <span class="sec-heading">Area of Improvement in your Daily work</span>
                </div>
                <table class="form-table review-data-table">
                    <tbody>
                        <tr v-for="(item, idx) in norm.summaryD" :key="'D'+idx">
                            <td class="cell-center font-bold row-index-cell" style="width: 6%;">{{ idx + 1 }}</td>
                            <td class="cell-text" style="width: 94%;">{{ item || '---' }}</td>
                        </tr>
                        <tr v-if="!norm.summaryD.length">
                            <td class="cell-center" style="width: 6%;">1</td>
                            <td class="empty-text" style="width: 94%;">No improvement areas recorded</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Section E: Feedback From Line Manager (Gold Banner) -->
            <div class="review-section-block">
                <div class="review-section-banner banner-gold">
                    <span class="sec-letter">E</span>
                    <span class="sec-heading">Feedback from your Line Manager</span>
                </div>
                <table class="form-table review-data-table">
                    <tbody>
                        <tr v-for="(item, idx) in norm.summaryE" :key="'E'+idx">
                            <td class="cell-center font-bold row-index-cell" style="width: 6%;">{{ idx + 1 }}</td>
                            <td class="cell-text" style="width: 94%;">{{ item || '---' }}</td>
                        </tr>
                        <tr v-if="!norm.summaryE.length">
                            <td class="cell-center" style="width: 6%;">1</td>
                            <td class="empty-text" style="width: 94%;">No manager feedback recorded</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Authorization & Sign-Off Section -->
            <div class="auth-signoff-section">
                <div class="auth-banner">
                    MANAGEMENT ENDORSEMENT &amp; FINAL APPROVAL
                </div>
                <table class="form-table auth-table">
                    <tbody>
                        <tr>
                            <!-- 1. Line Manager -->
                            <td class="auth-col" style="width: 33.33%;">
                                <div class="auth-col-num">1. Line Manager</div>
                                <div class="auth-field">
                                    <span class="auth-label">Name:</span>
                                    <span class="auth-val"><strong>{{ norm.auth.line_manager_name }}</strong></span>
                                </div>
                                <div class="auth-field">
                                    <span class="auth-label">Rating:</span>
                                    <span class="auth-val font-bold text-blue">{{ norm.auth.line_manager_rating }}</span>
                                </div>
                                <div class="auth-sig-box">
                                    <span class="sig-font-large">{{ norm.auth.line_manager_signature }}</span>
                                </div>
                                <div class="auth-field">
                                    <span class="auth-label">Date:</span>
                                    <span class="auth-val">{{ formatDate(norm.auth.line_manager_date) }}</span>
                                </div>
                            </td>

                            <!-- 2. HOD / Functional Head -->
                            <td class="auth-col" style="width: 33.33%;">
                                <div class="auth-col-num">2. HOD / Functional Head</div>
                                <div class="auth-comments-box">
                                    <div class="auth-label">Comments:</div>
                                    <div class="auth-comment-text">{{ norm.auth.hod_comments }}</div>
                                </div>
                                <div class="auth-field">
                                    <span class="auth-label">Name:</span>
                                    <span class="auth-val"><strong>{{ norm.auth.hod_name }}</strong></span>
                                </div>
                                <div class="auth-field">
                                    <span class="auth-label">Designation:</span>
                                    <span class="auth-val">{{ norm.auth.hod_designation }}</span>
                                </div>
                                <div class="auth-sig-box">
                                    <span class="sig-font-large">{{ norm.auth.hod_signature }}</span>
                                </div>
                                <div class="auth-field">
                                    <span class="auth-label">Date:</span>
                                    <span class="auth-val">{{ formatDate(norm.auth.hod_date) }}</span>
                                </div>
                            </td>

                            <!-- 3. Director / Management -->
                            <td class="auth-col" style="width: 33.33%;">
                                <div class="auth-col-num">3. Director / Management</div>
                                <div class="auth-comments-box">
                                    <div class="auth-label">Remarks:</div>
                                    <div class="auth-comment-text">{{ norm.auth.director_remarks }}</div>
                                </div>
                                <div class="auth-field">
                                    <span class="auth-label">Director:</span>
                                    <span class="auth-val"><strong>{{ norm.auth.director_name }}</strong></span>
                                </div>
                                <div class="auth-sig-box" style="margin-top: 24px;">
                                    <span class="sig-font-large">{{ norm.auth.director_signature }}</span>
                                </div>
                                <div class="auth-field">
                                    <span class="auth-label">Date:</span>
                                    <span class="auth-val">{{ formatDate(norm.auth.director_date) }}</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Page 3 Footer -->
            <div class="page-footer">
                <span>Page 03 &bull; End-Of-Year Review &amp; Sign-Off</span>
                <span>Melcom HR Performance Management System</span>
                <span>Verified Official Hardcopy Record</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from '@/helpers/pms_axios';

const props = defineProps({
    goal: {
        type: Object,
        required: true,
        default: () => ({})
    },
    employees: {
        type: Array,
        default: () => []
    }
});

const localEmployees = ref([]);

onMounted(async () => {
    if (props.employees && props.employees.length > 0) {
        localEmployees.value = props.employees;
    } else {
        try {
            const res = await axios.get('pms/get-employees');
            if (res.data?.status === 'success' && Array.isArray(res.data.data)) {
                localEmployees.value = res.data.data;
            }
        } catch (e) {
            // Ignore if error
        }
    }
});

// Parse helper for JSON strings or plain values
const parseMaybeJSON = (val, fallback = []) => {
    if (!val) return fallback;
    if (typeof val === 'object') return val;
    try {
        const parsed = JSON.parse(val);
        return parsed || fallback;
    } catch {
        return fallback;
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        const day = String(d.getDate()).padStart(2, '0');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const month = months[d.getMonth()];
        const year = d.getFullYear();
        return `${day}-${month}-${year}`;
    } catch {
        return dateStr;
    }
};

// 5 Standard Melcom Competencies
const defaultCompetencies = [
    {
        title: "Performance & Teamwork",
        weight: 20,
        selfRating: 3,
        managerRating: 3,
        descriptions: [
            "a) Overall performance - based on feedback from Line or Operations Managers",
            "b) Teamwork, People issues - how it has been managed, number of queries tracked as compared to last year and compared to your peers."
        ]
    },
    {
        title: "Customer Service / Relationship Building",
        weight: 20,
        selfRating: 3,
        managerRating: 3,
        descriptions: [
            "a) Number of super saver cards sold vs number of invoices made without the use of super saver card on the invoices",
            "b) Google scores – Improvement over last year's Shop Google or overall Melcom Google score."
        ]
    },
    {
        title: "Execution / Sales Results Driven",
        weight: 20,
        selfRating: 3,
        managerRating: 3,
        descriptions: [
            "a) Business Driven Metric (Set by Department with Management input)",
            "b) Loss to company % (Factors and calculations must be provided), where application and relevant"
        ]
    },
    {
        title: "Compliance & Quality Standards",
        weight: 20,
        selfRating: 3,
        managerRating: 3,
        descriptions: [
            "a) Adherence to company policies, SOPs, safety, and regulatory compliance",
            "b) % implementation of Wooqer checklist and shop/department standards"
        ]
    },
    {
        title: "Continuous Improvement in workflows/processes",
        weight: 20,
        selfRating: 3,
        managerRating: 3,
        descriptions: [
            "a) Culture of adaptability and innovation among staff, such as inventory management, employee training",
            "b) Adaptability / Flexibility",
            "c) Operational problem solving"
        ]
    }
];

const norm = computed(() => {
    const g = props.goal || {};
    const ad = parseMaybeJSON(g.appraisal_data, {});
    const u = g.user || {};

    // Competencies normalization
    let rawComps = ad.competencies || [];
    if (!Array.isArray(rawComps) || rawComps.length === 0) {
        rawComps = defaultCompetencies;
    }

    const competencies = defaultCompetencies.map((def, idx) => {
        const existing = rawComps.find(c => (c.title || '').trim().toLowerCase() === def.title.toLowerCase()) || rawComps[idx] || {};
        return {
            title: def.title,
            weight: Number(existing.weight || def.weight || 20),
            selfRating: Number(existing.selfRating !== undefined ? existing.selfRating : def.selfRating),
            managerRating: Number(existing.managerRating !== undefined ? existing.managerRating : def.managerRating),
            descriptions: def.descriptions
        };
    });

    // Score calculations
    let totalScoreWeighted = 0;
    let totalSelfWeighted = 0;
    let sumWeight = 0;
    competencies.forEach(c => {
        const w = Number(c.weight) || 20;
        sumWeight += w;
        totalScoreWeighted += ((Number(c.managerRating) || 0) * w) / 100;
        totalSelfWeighted += ((Number(c.selfRating) || 0) * w) / 100;
    });

    const managerOverallScore = totalScoreWeighted > 0 ? totalScoreWeighted : (parseFloat(ad.overall_manager_rating) || 3.0);
    const managerOverallPercentage = Math.round((managerOverallScore / 5.0) * 100);
    const selfAvgScore = totalSelfWeighted > 0 ? totalSelfWeighted : 3.0;

    // Narrative parsing
    const descriptions = Array.isArray(g.description) ? g.description : (Array.isArray(parseMaybeJSON(g.description, null)) ? parseMaybeJSON(g.description) : (g.description ? [g.description] : []));
    const purposes = Array.isArray(g.purposes) ? g.purposes : (Array.isArray(parseMaybeJSON(g.purposes, null)) ? parseMaybeJSON(g.purposes) : (g.purposes ? [g.purposes] : []));
    const challenges = Array.isArray(g.challenges) ? g.challenges : (Array.isArray(parseMaybeJSON(g.challenges, null)) ? parseMaybeJSON(g.challenges) : (g.challenges ? [g.challenges] : []));

    // SMART
    const sc = parseMaybeJSON(g.smart_criteria, {});

    // Quarterly
    let qList = parseMaybeJSON(g.quarterly_tracking, []);
    if (!Array.isArray(qList) || qList.length < 4) {
        const defaultQuarters = [
            { quarter: 'Q1', start_date: `${g.year || 2026}-01-01`, end_date: `${g.year || 2026}-03-31`, target_measures: [], attachments: [] },
            { quarter: 'Q2', start_date: `${g.year || 2026}-04-01`, end_date: `${g.year || 2026}-06-30`, target_measures: [], attachments: [] },
            { quarter: 'Q3', start_date: `${g.year || 2026}-07-01`, end_date: `${g.year || 2026}-09-30`, target_measures: [], attachments: [] },
            { quarter: 'Q4', start_date: `${g.year || 2026}-10-01`, end_date: `${g.year || 2026}-12-31`, target_measures: [], attachments: [] }
        ];
        qList = defaultQuarters.map((dq, idx) => {
            const eq = qList[idx] || {};
            return {
                quarter: dq.quarter,
                start_date: eq.start_date || dq.start_date,
                end_date: eq.end_date || dq.end_date,
                target_measures: Array.isArray(eq.target_measures) ? eq.target_measures : (typeof eq.target_measures === 'string' ? parseMaybeJSON(eq.target_measures, [eq.target_measures]) : []),
                attachments: Array.isArray(eq.attachments) ? eq.attachments : []
            };
        });
    }

    // Summary sections
    const rSummary = ad.review_summary || {};
    const summaryA = Array.isArray(rSummary.A) ? rSummary.A : (rSummary.A ? [rSummary.A] : []);
    const summaryB = Array.isArray(rSummary.B) ? rSummary.B : (rSummary.B ? [rSummary.B] : []);
    const summaryC = Array.isArray(rSummary.C) ? rSummary.C : (rSummary.C ? [rSummary.C] : []);
    const summaryD = Array.isArray(rSummary.D) ? rSummary.D : (rSummary.D ? [rSummary.D] : []);
    const summaryE = Array.isArray(rSummary.E) ? rSummary.E : (rSummary.E ? [rSummary.E] : []);

    // Authorization
    const rawAuth = (typeof ad.authorization === 'string') 
        ? parseMaybeJSON(ad.authorization, {}) 
        : (ad.authorization || {});

    const auth = {
        line_manager_name: (rawAuth.line_manager_name || g.manager_name || ad.manager_signature_name || '').trim(),
        line_manager_rating: (rawAuth.line_manager_rating || (managerOverallScore > 0 ? `${managerOverallScore.toFixed(2)} / 5.00` : '')).trim(),
        line_manager_signature: (rawAuth.line_manager_signature || ad.manager_signature_name || g.manager_name || '').trim(),
        line_manager_date: (rawAuth.line_manager_date || ad.signature_date || g.updated_at || '').trim(),

        hod_comments: (rawAuth.hod_comments || ad.hod_comments || '').trim(),
        hod_name: (rawAuth.hod_name || ad.hod_signature_name || '').trim(),
        hod_designation: (rawAuth.hod_designation || '').trim(),
        hod_signature: (rawAuth.hod_signature || ad.hod_signature_name || '').trim(),
        hod_date: (rawAuth.hod_date || ad.hod_signature_date || '').trim(),

        director_remarks: (rawAuth.director_remarks || ad.director_remarks || '').trim(),
        director_name: (rawAuth.director_name || ad.director_signature_name || '').trim(),
        director_signature: (rawAuth.director_signature || ad.director_signature_name || '').trim(),
        director_date: (rawAuth.director_date || ad.director_signature_date || '').trim()
    };

    // Robust resolution for Candidate Position / Job Title
    let candidateJobTitle = (g.job_title && g.job_title !== 'Employee' && g.job_title !== 'N/A') ? g.job_title : '';
    if (!candidateJobTitle) {
        candidateJobTitle = (g.position && g.position !== 'Employee' && g.position !== 'N/A') ? g.position : '';
    }
    if (!candidateJobTitle) {
        candidateJobTitle = (g.joining_position && g.joining_position !== 'Employee' && g.joining_position !== 'N/A') ? g.joining_position : '';
    }
    if (!candidateJobTitle) {
        candidateJobTitle = (g.joiningposition && g.joiningposition !== 'Employee' && g.joiningposition !== 'N/A') ? g.joiningposition : '';
    }

    // Lookup in employees list by employee_code or candidate_name
    const allEmps = (props.employees && props.employees.length > 0) ? props.employees : localEmployees.value;
    const empCode = (g.employee_code || u.employee_code || '').trim().toLowerCase();
    const candName = (g.candidate_name || u.name || '').trim().toLowerCase();

    if (!candidateJobTitle && allEmps && allEmps.length > 0) {
        const matchedEmp = allEmps.find(e => 
            (e.employee_code && e.employee_code.trim().toLowerCase() === empCode) ||
            (e.name && e.name.trim().toLowerCase() === candName)
        );
        if (matchedEmp) {
            const empPos = matchedEmp.position || matchedEmp.job_title || matchedEmp.joiningposition;
            if (empPos && empPos !== 'N/A' && empPos !== 'Employee') {
                candidateJobTitle = empPos;
            }
        }
    }

    // Check appraisal_data if any position is stored there
    if (!candidateJobTitle && ad.job_title && ad.job_title !== 'Employee' && ad.job_title !== 'N/A') {
        candidateJobTitle = ad.job_title;
    }

    // Final fallbacks
    if (!candidateJobTitle) {
        candidateJobTitle = (g.designation && g.designation !== 'Employee' && g.designation !== 'N/A') 
            ? g.designation 
            : ((u.designation && u.designation !== 'Employee' && u.designation !== 'N/A') ? u.designation : (g.department || 'Employee'));
    }

    return {
        candidateName: g.candidate_name || u.name || 'Staff Member',
        employeeCode: g.employee_code || u.employee_code || 'EMP-000',
        department: g.department || u.department || 'Head Office',
        location: g.location || u.location || 'Accra',
        jobTitle: candidateJobTitle,
        lineManagerName: g.manager_name || auth.line_manager_name || ad.manager_signature_name || 'Line Manager',
        lineManagerTitle: g.manager_designation || 'Line Manager',
        year: g.year || new Date().getFullYear(),
        createdDate: g.created_at,
        targetDate: g.target_date || `${g.year || 2026}-12-31`,
        title: g.title || 'FY Performance & Operational Goals',
        category: g.category || 'General',
        target: g.target || 100,
        descriptions,
        purposes,
        challenges,
        smartCriteria: {
            specific: Boolean(sc.specific),
            measurable: Boolean(sc.measurable),
            attainable: Boolean(sc.attainable),
            relevant: Boolean(sc.relevant),
            time_bound: Boolean(sc.time_bound)
        },
        quarterly: qList,
        competencies,
        managerOverallScore,
        managerOverallPercentage,
        selfAvgScore,
        impressedMost: ad.impressedMost || '',
        impressedLeast: ad.impressedLeast || '',
        candidateSignature: ad.candidate_signature_name || g.candidate_name,
        candidateSignatureDate: ad.signature_date || g.created_at,
        managerSignature: ad.manager_signature_name || auth.line_manager_signature || g.manager_name,
        managerSignatureDate: ad.signature_date || auth.line_manager_date || g.updated_at,
        appraisalDate: auth.line_manager_date || ad.signature_date || g.updated_at,
        reviewDate: auth.line_manager_date || g.updated_at,
        summaryA,
        summaryB,
        summaryC,
        summaryD,
        summaryE,
        auth,
        performanceRating: Number(ad.performanceRating || (Math.round(managerOverallScore) > 0 ? Math.round(managerOverallScore) : 0)),
        ratingComments: (() => {
            const raw = (typeof ad.rating_comments === 'string') 
                ? parseMaybeJSON(ad.rating_comments, {}) 
                : (ad.rating_comments || {});
            const selectedR = Number(ad.performanceRating || Math.round(managerOverallScore) || 0);
            const fallbackC = (ad.performanceComments || '').trim();
            return {
                1: ((raw[1] || raw['1'] || (selectedR === 1 ? fallbackC : '')) || '').trim(),
                2: ((raw[2] || raw['2'] || (selectedR === 2 ? fallbackC : '')) || '').trim(),
                3: ((raw[3] || raw['3'] || (selectedR === 3 ? fallbackC : '')) || '').trim(),
                4: ((raw[4] || raw['4'] || (selectedR === 4 ? fallbackC : '')) || '').trim(),
                5: ((raw[5] || raw['5'] || (selectedR === 5 ? fallbackC : '')) || '').trim()
            };
        })()
    };
});

const smartCount = computed(() => {
    return Object.values(norm.value.smartCriteria).filter(Boolean).length;
});

const totalWeight = computed(() => {
    return norm.value.competencies.reduce((acc, c) => acc + (Number(c.weight) || 0), 0);
});

const getCompWeightedScore = (comp) => {
    const mgr = Number(comp.managerRating) || 0;
    const wt = Number(comp.weight) || 20;
    // Format to match exact template (e.g. 3.00, 1.00, 2.00)
    return (mgr * (wt / 20)).toFixed(2);
};

const dossierId = computed(() => {
    const g = props.goal || {};
    const deptPrefix = g.department ? g.department.substring(0, 3).toUpperCase() : 'PMS';
    return `${deptPrefix}-${g.employee_code || g.id || '001'}`;
});
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════════════════ */
/* PRINT-OPTIMIZED HARDCOPY STYLES                                        */
/* Solid deep black text, high-contrast borders, exact template colors    */
/* ═══════════════════════════════════════════════════════════════════════ */
.hardcopy-dossier-root {
    font-family: Arial, Helvetica, sans-serif !important;
    color: #000000 !important;
    background: transparent;
    width: 100%;
    margin: 0 auto;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
}

/* Individual A4 Page Container */
.hardcopy-page {
    background: #ffffff !important;
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto 30px auto;
    padding: 12mm 14mm 14mm 14mm;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    position: relative;
    border: 1px solid #d1d5db;
}

/* Standard Header Banners */
.header-banner {
    text-align: center;
    font-weight: 900;
    font-size: 15pt;
    padding: 7px 12px;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    border: 1px solid #000000;
}
.banner-grey {
    background-color: #595959 !important;
    color: #ffffff !important;
}
.banner-blue {
    background-color: #2F5597 !important;
    color: #ffffff !important;
}

/* Form Tables & Cells */
.form-table {
    width: 100%;
    border-collapse: collapse !important;
    border: 1px solid #000000 !important;
    margin-bottom: 8px;
    font-size: 9pt;
    color: #000000 !important;
}
.form-table th, 
.form-table td {
    border: 1px solid #000000 !important;
    padding: 4px 6px;
    vertical-align: middle;
    box-sizing: border-box;
    color: #000000 !important;
}

/* Meta Table */
.meta-table .cell-label {
    background-color: #f2f2f2 !important;
    font-weight: bold;
    font-size: 8.5pt;
    color: #000000 !important;
}
.meta-table .cell-value {
    background-color: #ffffff !important;
    font-size: 8.5pt;
}

/* Signature Styling */
.sig-font {
    font-family: 'Brush Script MT', 'Segoe Script', cursive !important;
    font-size: 13pt;
    color: #002060 !important;
    font-weight: bold;
    margin-right: 6px;
}
.sig-font-large {
    font-family: 'Brush Script MT', 'Segoe Script', cursive !important;
    font-size: 15pt;
    color: #002060 !important;
    font-weight: bold;
    display: block;
    text-align: center;
}
.sig-date {
    font-size: 8pt;
    color: #333333 !important;
}

/* Section Box & Banners */
.section-box {
    border: 1px solid #000000;
    margin-bottom: 6px;
}
.section-banner {
    padding: 3px 8px;
    border-bottom: 1px solid #000000;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.banner-lavender { background-color: #D9D9E8 !important; color: #000000 !important; }
.banner-iceblue { background-color: #D6E4F0 !important; color: #000000 !important; }
.banner-sage { background-color: #DCE7DB !important; color: #000000 !important; text-align: center; flex-direction: column; padding: 4px 8px; }
.banner-pink { background-color: #F4A6A6 !important; color: #000000 !important; }
.banner-lightgreen { background-color: #C6EFCE !important; color: #000000 !important; }
.banner-purple { background-color: #CCC0DA !important; color: #000000 !important; }
.banner-green { background-color: #A8D08D !important; color: #000000 !important; }
.banner-gold { background-color: #FFD966 !important; color: #000000 !important; }

.sec-title {
    font-weight: 900;
    font-size: 9.5pt;
    text-transform: uppercase;
}
.sec-subtitle {
    font-size: 7.5pt;
    font-style: italic;
    color: #333333 !important;
}
.section-body {
    padding: 5px 8px;
    font-size: 8.5pt;
    background-color: #ffffff;
}

/* Primary Goal Row */
.primary-goal-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}
.badge-cat {
    background-color: #002060;
    color: #ffffff;
    font-size: 7pt;
    font-weight: bold;
    padding: 1px 5px;
    border-radius: 3px;
    text-transform: uppercase;
}
.goal-title-text {
    font-size: 9.5pt;
    color: #002060;
    text-transform: uppercase;
}
.target-chip {
    margin-left: auto;
    font-weight: bold;
    font-size: 8pt;
    background: #eef2ff;
    padding: 1px 6px;
    border: 1px solid #c7d2fe;
    border-radius: 3px;
}

/* Lists */
.numbered-list, .bullet-list {
    margin: 2px 0 0 16px;
    padding: 0;
    line-height: 1.35;
    font-size: 8.5pt;
}
.numbered-list li { margin-bottom: 2px; }
.bullet-list li { margin-bottom: 2px; }
.empty-text { font-style: italic; color: #666666 !important; font-size: 8pt; margin: 0; }

/* 2-Column Split for Goals & SMART Table */
.goals-smart-split {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
}
.goals-narrative-col {
    flex: 1 1 73%;
    min-width: 0;
}
.smart-table-col {
    flex: 0 0 26%;
    min-width: 0;
}

/* SMART Table */
.smart-table th {
    font-size: 8pt;
    padding: 4px;
}
.header-row-dark {
    background-color: #000000 !important;
    color: #ffffff !important;
}
.header-row-dark th { color: #ffffff !important; }
.smart-name {
    font-size: 8pt;
    font-weight: bold;
    background-color: #ffffff;
}
.smart-check {
    text-align: center;
    font-weight: 900;
    font-size: 9pt;
    padding: 3px;
    position: relative;
}
.cell-s { background-color: #FAD2B8 !important; }
.cell-m { background-color: #D4EDDA !important; }
.cell-a { background-color: #D1ECF1 !important; }
.cell-r { background-color: #E2D9F3 !important; }
.cell-t { background-color: #F8D7DA !important; }
.checkmark-symbol {
    font-size: 11pt;
    font-weight: bold;
    color: #000000;
    margin-left: 2px;
}

/* Completion Date Box */
.completion-date-box {
    border: 1px solid #000000;
    margin-top: 4px;
    text-align: center;
}
.completion-header {
    background-color: #E8D5D5 !important;
    font-weight: 900;
    font-size: 8pt;
    padding: 3px;
    border-bottom: 1px solid #000000;
}
.completion-val {
    padding: 6px;
    font-size: 9.5pt;
    font-weight: bold;
    background-color: #ffffff;
}
.criteria-score-tag {
    text-align: center;
    font-size: 7.5pt;
    font-weight: bold;
    padding: 2px;
    background: #f1f5f9;
    border: 1px solid #000000;
    margin-top: 4px;
}

/* Quarterly Table */
.quarterly-section {
    border: 1px solid #000000;
    margin-bottom: auto;
}
.quarterly-table {
    margin-bottom: 0;
    border-top: none !important;
}
.q-head {
    font-weight: 900;
    font-size: 10pt;
    text-align: center;
    padding: 3px;
}
.q1-banner { background-color: #FAD2B8 !important; color: #000000 !important; }
.q2-banner { background-color: #B8E2F2 !important; color: #000000 !important; }
.q3-banner { background-color: #D0C4DF !important; color: #000000 !important; }
.q4-banner { background-color: #A8C686 !important; color: #000000 !important; }
.q-subhead-row th {
    background-color: #f2f2f2 !important;
    font-size: 7.5pt;
    text-align: center;
    padding: 2px;
}
.q-cell {
    padding: 4px 5px !important;
    font-size: 7.5pt;
    background-color: #ffffff;
}
.q-date-range {
    font-size: 7pt;
    border-bottom: 1px dashed #cccccc;
    padding-bottom: 2px;
    margin-bottom: 3px;
}
.q-sub-title {
    font-weight: bold;
    font-size: 7pt;
    text-transform: uppercase;
    color: #002060 !important;
    margin-top: 2px;
}
.q-measure-list {
    margin: 1px 0 0 12px;
    padding: 0;
    font-size: 7.5pt;
}
.q-evidence-list {
    font-size: 7pt;
    color: #444444;
}
.evidence-tag {
    display: block;
    word-break: break-all;
}

/* ═══════════════════════════════════════════════════════════════════════ */
/* PAGE 2 SPECIFIC STYLES                                                 */
/* ═══════════════════════════════════════════════════════════════════════ */
.rating-highlight-blue {
    color: #002060 !important;
    font-weight: 900;
    font-size: 11pt;
}
.header-row-lightblue {
    background-color: #BDD7EE !important;
    color: #000000 !important;
}
.header-row-lightblue th {
    color: #000000 !important;
    font-weight: bold;
    font-size: 8.5pt;
    padding: 5px 4px;
}
.comp-head-title { font-weight: 900; font-size: 9.5pt; font-style: italic; }
.comp-head-sub { font-size: 7.5pt; font-weight: normal; font-style: normal; }

.comp-table {
    font-size: 8.5pt;
}
.comp-desc-cell {
    padding: 4px 6px !important;
}
.comp-row-title {
    font-size: 9pt;
    color: #002060 !important;
}
.comp-criteria-list {
    margin-top: 2px;
    padding-left: 4px;
}
.comp-crit-item {
    font-size: 7.5pt;
    line-height: 1.25;
    color: #222222 !important;
}
.cell-center { text-align: center !important; }
.font-bold { font-weight: bold !important; }
.font-black { font-weight: 900 !important; }
.text-blue { color: #002060 !important; }
.text-darkblue { color: #1E3A8A !important; font-size: 10pt; }

.comp-summary-row td {
    background-color: #D9E1F2 !important;
    font-weight: bold;
    font-size: 9pt;
    border-top: 2px solid #000000 !important;
}
.rating-final-cell {
    font-size: 11pt !important;
    color: #002060 !important;
}

/* Feedback Split Box */
.feedback-split-box {
    display: flex;
    border: 1px solid #000000;
    margin-bottom: 8px;
}
.feedback-col {
    flex: 1 1 50%;
    box-sizing: border-box;
}
.feedback-col:first-child {
    border-right: 1px solid #000000;
}
.feedback-col-header {
    background-color: #f2f2f2;
    font-weight: bold;
    font-size: 8.5pt;
    padding: 4px 8px;
    border-bottom: 1px solid #000000;
    text-align: center;
}
.feedback-col-body {
    padding: 6px 8px;
    font-size: 8.5pt;
    min-height: 38px;
    background-color: #ffffff;
    line-height: 1.35;
}

/* Scale Table */
.scale-guide-wrapper {
    margin-bottom: auto;
}
.scale-table {
    font-size: 7.5pt;
    margin-bottom: 0;
}
.scale-table th {
    padding: 3px 6px;
    font-size: 8pt;
}
.scale-table td {
    padding: 2px 6px;
}
.scale-cell-band {
    background-color: #fafafa;
}
.active-rating-row td {
    background-color: #E2EFDA !important;
    font-weight: bold;
}

/* ═══════════════════════════════════════════════════════════════════════ */
/* PAGE 3 SPECIFIC STYLES                                                 */
/* ═══════════════════════════════════════════════════════════════════════ */
.review-main-header {
    text-align: center;
    font-weight: 900;
    font-size: 15pt;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    color: #000000 !important;
}
.header-cyan {
    background-color: #00A2E8 !important;
    color: #ffffff !important;
}
.header-cyan th {
    color: #ffffff !important;
    font-weight: bold;
    font-size: 8.5pt;
    padding: 4px;
    text-align: center;
}
.review-meta-vals td {
    font-size: 8.5pt;
    padding: 4px 6px;
    background-color: #ffffff;
}

/* Review Sections A-E */
.review-section-block {
    border: 1px solid #000000;
    margin-bottom: 6px;
}
.review-section-banner {
    padding: 3px 8px;
    border-bottom: 1px solid #000000;
    display: flex;
    align-items: center;
    gap: 8px;
}
.sec-letter {
    font-weight: 900;
    font-size: 10pt;
    border-right: 1px solid #000000;
    padding-right: 8px;
}
.sec-heading {
    font-weight: bold;
    font-size: 8.5pt;
}
.review-data-table {
    margin-bottom: 0;
    border: none !important;
}
.review-data-table th, .review-data-table td {
    border-left: none !important;
    border-right: none !important;
    padding: 3px 6px;
    font-size: 8pt;
}
.review-data-table tr:last-child td {
    border-bottom: none !important;
}
.subhead-row th {
    background-color: #f2f2f2 !important;
    font-size: 7.5pt;
    padding: 2px 6px;
}
.row-index-cell {
    background-color: #f8fafc;
    border-right: 1px solid #000000 !important;
}
.cell-text {
    line-height: 1.3;
}

/* Authorization & Sign-Off Section */
.auth-signoff-section {
    border: 1px solid #000000;
    margin-top: 6px;
    margin-bottom: auto;
}
.auth-banner {
    background-color: #002060 !important;
    color: #ffffff !important;
    text-align: center;
    font-weight: 900;
    font-size: 9pt;
    padding: 4px;
    letter-spacing: 0.5px;
}
.auth-table {
    margin-bottom: 0;
    border: none !important;
}
.auth-col {
    padding: 6px !important;
    vertical-align: top;
    background-color: #ffffff;
}
.auth-col-num {
    font-weight: 900;
    font-size: 8.5pt;
    color: #002060;
    border-bottom: 1px solid #000000;
    padding-bottom: 2px;
    margin-bottom: 4px;
}
.auth-field {
    font-size: 7.5pt;
    margin-bottom: 3px;
    display: flex;
    justify-content: space-between;
}
.auth-label { font-weight: bold; color: #444444; }
.auth-val { font-size: 8pt; }
.auth-sig-box {
    border-bottom: 1px solid #000000;
    min-height: 32px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    margin: 6px 0;
    padding-bottom: 2px;
}
.auth-comments-box {
    border: 1px solid #cccccc;
    padding: 3px 5px;
    min-height: 40px;
    background: #fdfdfd;
    margin-bottom: 4px;
}
.auth-comment-text {
    font-size: 7.5pt;
    line-height: 1.25;
    font-style: italic;
    color: #222222;
}

/* Page Footer */
.page-footer {
    display: flex;
    justify-content: space-between;
    font-size: 7pt;
    color: #444444 !important;
    border-top: 1px solid #cccccc;
    padding-top: 4px;
    margin-top: 8px;
    text-transform: uppercase;
}

/* ═══════════════════════════════════════════════════════════════════════ */
/* EXACT PRINT MEDIA RULES FOR A4 HARDCOPY                                */
/* ═══════════════════════════════════════════════════════════════════════ */
@media print {
    .hardcopy-page {
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

    .hardcopy-page:last-child {
        page-break-after: avoid !important;
        break-after: avoid !important;
    }

    table {
        page-break-inside: auto !important;
    }
    tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
    thead {
        display: table-header-group !important;
    }
    .section-box, .quarterly-section, .feedback-split-box, .scale-guide-wrapper, .review-section-block, .auth-signoff-section {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    @page {
        size: A4 portrait;
        margin: 6mm 6mm;
    }
}
</style>
