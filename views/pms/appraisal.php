<?php
/**
 * Yearly Appraisal View
 * Employee self-assessment and performance review form
 */

$currentYear = date('Y');
$years = range($currentYear, $currentYear - 5);

// Sample goals for the current year
$goals = [
    ['id' => 1, 'title' => 'Complete Project Alpha', 'weight' => 25, 'target' => 'Deliver by Q2', 'rating' => 0, 'review' => ''],
    ['id' => 2, 'title' => 'Improve Team Productivity', 'weight' => 20, 'target' => '15% increase', 'rating' => 0, 'review' => ''],
    ['id' => 3, 'title' => 'Learn New Technology Stack', 'weight' => 15, 'target' => 'Complete certification', 'rating' => 0, 'review' => ''],
    ['id' => 4, 'title' => 'Reduce Customer Complaints', 'weight' => 20, 'target' => '30% reduction', 'rating' => 0, 'review' => ''],
    ['id' => 5, 'title' => 'Mentor Junior Developers', 'weight' => 20, 'target' => 'Mentor 3 juniors', 'rating' => 0, 'review' => ''],
];
?>

<div class="page-header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Yearly Appraisal</h1>
            <p class="page-subtitle">Complete your performance review and self-assessment</p>
        </div>
        <div class="flex gap-sm">
            <select class="form-control" id="year-selector" style="width: 120px;">
                <?php foreach ($years as $year): ?>
                <option value="<?php echo $year; ?>" <?php echo $year == $currentYear ? 'selected' : ''; ?>>
                    <?php echo $year; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<!-- Goals Section -->
<div class="card mb-lg">
    <div class="card-header">
        <h3 class="card-title">Goals & Objectives</h3>
        <span class="badge badge-info">5 Goals for <?php echo $currentYear; ?></span>
    </div>
    
    <div class="table-scroll">
        <table class="data-table" id="goals-table">
            <thead>
                <tr>
                    <th style="width: 30%;">Goal</th>
                    <th style="width: 10%;">Weight</th>
                    <th style="width: 15%;">Target</th>
                    <th style="width: 15%;">Self Rating</th>
                    <th style="width: 30%;">Review/Comments</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($goals as $goal): ?>
                <tr data-goal-id="<?php echo $goal['id']; ?>">
                    <td>
                        <strong><?php echo htmlspecialchars($goal['title']); ?></strong>
                    </td>
                    <td><?php echo $goal['weight']; ?>%</td>
                    <td><?php echo htmlspecialchars($goal['target']); ?></td>
                    <td>
                        <select class="form-control goal-rating" name="goal_rating_<?php echo $goal['id']; ?>">
                            <option value="">Select</option>
                            <option value="1">1 - Did Not Meet</option>
                            <option value="2">2 - Partially Met</option>
                            <option value="3">3 - Met Expectations</option>
                            <option value="4">4 - Exceeded</option>
                            <option value="5">5 - Greatly Exceeded</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control goal-review" 
                               name="goal_review_<?php echo $goal['id']; ?>"
                               placeholder="Add your review...">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Appraisal Form -->
<form id="appraisal-form" class="appraisal-form">
    <input type="hidden" name="appraisal_year" value="<?php echo $currentYear; ?>">
    
    <!-- Performance Criteria -->
    <div class="card mb-lg">
        <div class="card-header">
            <h3 class="card-title">Performance Criteria</h3>
            <span class="text-muted">Rate yourself on each competency (1-5)</span>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; padding: 1rem 0;">
            <?php
            $criteria = [
                ['name' => 'quality', 'label' => 'Quality of Work', 'desc' => 'Accuracy, thoroughness, and reliability of work'],
                ['name' => 'productivity', 'label' => 'Productivity', 'desc' => 'Volume of work and meeting deadlines'],
                ['name' => 'teamwork', 'label' => 'Teamwork', 'desc' => 'Collaboration and contribution to team goals'],
                ['name' => 'communication', 'label' => 'Communication', 'desc' => 'Clarity and effectiveness in communication'],
                ['name' => 'problem_solving', 'label' => 'Problem Solving', 'desc' => 'Analytical thinking and resolution skills'],
                ['name' => 'initiative', 'label' => 'Initiative', 'desc' => 'Self-motivation and proactive approach'],
                ['name' => 'reliability', 'label' => 'Reliability', 'desc' => 'Dependability and consistency'],
            ];
            
            foreach ($criteria as $criterion):
            ?>
            <div class="form-group">
                <label class="form-label"><?php echo $criterion['label']; ?></label>
                <p class="text-muted" style="font-size: 0.8rem; margin-bottom: 0.5rem;"><?php echo $criterion['desc']; ?></p>
                <div class="rating-selector" data-name="<?php echo $criterion['name']; ?>_rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <label class="rating-option">
                        <input type="radio" name="<?php echo $criterion['name']; ?>_rating" value="<?php echo $i; ?>">
                        <span><?php echo $i; ?></span>
                    </label>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Self Assessment -->
    <div class="card mb-lg">
        <div class="card-header">
            <h3 class="card-title">Self Assessment</h3>
        </div>
        
        <div class="form-group">
            <label class="form-label">Reflect on your overall performance this year</label>
            <textarea class="form-control" name="self_assessment" rows="4" 
                      placeholder="Describe your overall performance, key contributions, and how you've grown professionally this year..."></textarea>
        </div>
    </div>
    
    <!-- Achievements -->
    <div class="card mb-lg">
        <div class="card-header">
            <h3 class="card-title">Key Achievements</h3>
        </div>
        
        <div class="form-group">
            <label class="form-label">List your significant accomplishments this year</label>
            <textarea class="form-control" name="achievements" rows="4" 
                      placeholder="• Completed Project X ahead of schedule&#10;• Improved team efficiency by 20%&#10;• Received positive client feedback on deliverables"></textarea>
        </div>
    </div>
    
    <!-- Areas for Improvement -->
    <div class="card mb-lg">
        <div class="card-header">
            <h3 class="card-title">Areas for Improvement</h3>
        </div>
        
        <div class="form-group">
            <label class="form-label">Identify areas where you can grow and improve</label>
            <textarea class="form-control" name="areas_for_improvement" rows="4" 
                      placeholder="Be honest about areas where you could improve. This helps in creating an effective development plan."></textarea>
        </div>
    </div>
    
    <!-- Development Plan -->
    <div class="card mb-lg">
        <div class="card-header">
            <h3 class="card-title">Development Plan & Training Needs</h3>
        </div>
        
        <div class="form-group">
            <label class="form-label">What skills or training would help you perform better?</label>
            <textarea class="form-control" name="development_plan" rows="4" 
                      placeholder="List any courses, certifications, or skills you'd like to develop..."></textarea>
        </div>
    </div>
    
    <!-- Goals for Next Year -->
    <div class="card mb-lg">
        <div class="card-header">
            <h3 class="card-title">Goals for Next Year</h3>
        </div>
        
        <div class="form-group">
            <label class="form-label">What are your objectives for the upcoming year?</label>
            <textarea class="form-control" name="goals_next_year" rows="4" 
                      placeholder="Set SMART goals (Specific, Measurable, Achievable, Relevant, Time-bound)..."></textarea>
        </div>
    </div>
    
    <!-- Submit Section -->
    <div class="card">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-muted" style="font-size: 0.85rem;">
                    <span class="material-symbols-sharp" style="font-size: 1rem; vertical-align: middle;">info</span>
                    Save as draft to continue later, or submit for manager review.
                </p>
                <p id="autosave-status" class="text-muted" style="font-size: 0.8rem; margin-top: 4px;"></p>
            </div>
            <div class="flex gap-md">
                <button type="button" class="btn btn-outline btn-lg" id="save-draft-btn">
                    <span class="material-symbols-sharp">save</span>
                    Save Draft
                </button>
                <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
                    <span class="material-symbols-sharp">send</span>
                    Submit for Review
                </button>
            </div>
        </div>
    </div>
</form>

<style>
.rating-selector {
    display: flex;
    gap: 0.5rem;
}

.rating-option {
    cursor: pointer;
}

.rating-option input {
    display: none;
}

.rating-option span {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border: 2px solid var(--color-light);
    border-radius: var(--radius-md);
    font-weight: 600;
    transition: var(--transition-fast);
}

.rating-option:hover span {
    border-color: var(--color-primary);
    background: rgba(124, 58, 237, 0.05);
}

.rating-option input:checked + span {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: white;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('appraisal-form');
    const saveDraftBtn = document.getElementById('save-draft-btn');
    const submitBtn = document.getElementById('submit-btn');
    const autosaveStatus = document.getElementById('autosave-status');
    const yearSelector = document.getElementById('year-selector');
    
    // Load saved draft
    loadDraft();
    
    // Auto-save every 30 seconds
    setInterval(saveDraft, 30000);
    
    // Save draft button
    saveDraftBtn.addEventListener('click', function() {
        saveDraft();
        Toast.success('Draft saved successfully');
    });
    
    // Submit form
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateAppraisal()) {
            Toast.warning('Please fill in all required fields');
            return;
        }
        
        submitAppraisal();
    });
    
    // Year change
    yearSelector.addEventListener('change', function() {
        const year = this.value;
        loadDraft(year);
        Toast.info('Loaded data for ' + year);
    });
    
    function saveDraft() {
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => data[key] = value);
        
        // Add goal ratings
        document.querySelectorAll('.goal-rating').forEach(select => {
            data[select.name] = select.value;
        });
        document.querySelectorAll('.goal-review').forEach(input => {
            data[input.name] = input.value;
        });
        
        const year = yearSelector.value;
        localStorage.setItem('appraisal_draft_' + year, JSON.stringify(data));
        
        autosaveStatus.textContent = 'Draft auto-saved at ' + new Date().toLocaleTimeString();
    }
    
    function loadDraft(year) {
        year = year || yearSelector.value;
        const saved = localStorage.getItem('appraisal_draft_' + year);
        
        if (saved) {
            const data = JSON.parse(saved);
            
            // Restore form fields
            Object.keys(data).forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field) {
                    if (field.type === 'radio') {
                        const radio = form.querySelector(`[name="${key}"][value="${data[key]}"]`);
                        if (radio) radio.checked = true;
                    } else {
                        field.value = data[key];
                    }
                }
            });
            
            // Restore goal fields
            document.querySelectorAll('.goal-rating').forEach(select => {
                if (data[select.name]) select.value = data[select.name];
            });
            document.querySelectorAll('.goal-review').forEach(input => {
                if (data[input.name]) input.value = data[input.name];
            });
            
            autosaveStatus.textContent = 'Draft loaded from previous session';
        }
    }
    
    function validateAppraisal() {
        let isValid = true;
        
        // Check if at least self-assessment is filled
        const selfAssessment = form.querySelector('[name="self_assessment"]');
        if (!selfAssessment.value.trim()) {
            selfAssessment.classList.add('error');
            isValid = false;
        }
        
        return isValid;
    }
    
    function submitAppraisal() {
        showLoading(submitBtn);
        
        const formData = new FormData(form);
        
        // Add goal data
        document.querySelectorAll('.goal-rating').forEach(select => {
            formData.append(select.name, select.value);
        });
        document.querySelectorAll('.goal-review').forEach(input => {
            formData.append(input.name, input.value);
        });
        
        // Simulate API call (replace with actual endpoint)
        setTimeout(() => {
            // Store in localStorage for HR page to access
            const submittedData = {};
            formData.forEach((value, key) => submittedData[key] = value);
            submittedData.submitted_at = new Date().toISOString();
            submittedData.status = 'submitted';
            submittedData.employee_name = 'John Doe'; // Replace with actual user
            
            const submissions = JSON.parse(localStorage.getItem('hr_submissions') || '[]');
            submissions.push(submittedData);
            localStorage.setItem('hr_submissions', JSON.stringify(submissions));
            
            // Clear draft
            localStorage.removeItem('appraisal_draft_' + yearSelector.value);
            
            hideLoading(submitBtn);
            Toast.success('Appraisal submitted successfully!');
            
            // Redirect to dashboard after delay
            setTimeout(() => {
                window.location.href = 'index.php?page=pms-dashboard';
            }, 2000);
        }, 1500);
    }
});
</script>
