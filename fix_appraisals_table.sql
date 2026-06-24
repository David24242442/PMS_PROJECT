-- Add missing columns to appraisals table
ALTER TABLE appraisals ADD COLUMN IF NOT EXISTS achievements TEXT NULL;
ALTER TABLE appraisals ADD COLUMN IF NOT EXISTS improvements TEXT NULL;
ALTER TABLE appraisals ADD COLUMN IF NOT EXISTS development_plan TEXT NULL;
ALTER TABLE appraisals ADD COLUMN IF NOT EXISTS competencies_data JSON NULL;
ALTER TABLE appraisals ADD COLUMN IF NOT EXISTS goals_data JSON NULL;
ALTER TABLE appraisals ADD COLUMN IF NOT EXISTS overall_rating DECIMAL(5,2) NULL;
ALTER TABLE appraisals ADD COLUMN IF NOT EXISTS candidate_name VARCHAR(255) NULL;
ALTER TABLE appraisals ADD COLUMN IF NOT EXISTS manager_signature_name VARCHAR(255) NULL;
ALTER TABLE appraisals ADD COLUMN IF NOT EXISTS candidate_signature_name VARCHAR(255) NULL;
ALTER TABLE appraisals ADD COLUMN IF NOT EXISTS signature_date DATE NULL;

-- Ensure status column exists and has proper enum/varchar
ALTER TABLE appraisals MODIFY COLUMN status VARCHAR(50) DEFAULT 'draft';
