-- Add performance indexes
ALTER TABLE `student_enroll` ADD INDEX `idx_student_course` (`student_id`, `course_id`);
ALTER TABLE `module` ADD INDEX `idx_course_module` (`course_id`, `module_id`);
ALTER TABLE `feedback_survey` ADD INDEX `idx_course_module_dates` (`course_id`, `module_id`, `start_date`, `end_date`);
ALTER TABLE `feedback_done` ADD INDEX `idx_survey_student` (`survey_id`, `student_id`);

-- Create optimized view for surveys
CREATE OR REPLACE VIEW student_surveys AS
SELECT 
    se.student_id,
    se.course_id,
    m.module_id,
    m.module_name,
    fs.survey_id,
    fs.start_date,
    fs.end_date
FROM 
    student_enroll se
JOIN 
    module m ON se.course_id = m.course_id
LEFT JOIN 
    feedback_survey fs ON m.course_id = fs.course_id 
    AND m.module_id = fs.module_id
    AND CURDATE() BETWEEN fs.start_date AND fs.end_date
LEFT JOIN 
    feedback_done fd ON fs.survey_id = fd.survey_id 
    AND se.student_id = fd.student_id
WHERE 
    se.student_enroll_status = 'Following'
    AND fd.survey_id IS NULL;

-- Optimize tables
OPTIMIZE TABLE student_enroll, module, feedback_survey, feedback_done;
