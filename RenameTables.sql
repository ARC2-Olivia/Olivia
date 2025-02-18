/* PHASE 1 */
RENAME TABLE evaluation TO practical_submodule;

RENAME TABLE evaluation_question TO practical_submodule_question;
RENAME TABLE evaluation_question_answer TO practical_submodule_question_answer;

RENAME TABLE evaluation_assessment TO practical_submodule_assessment;
RENAME TABLE evaluation_assessment_answer TO practical_submodule_assessment_answer;

RENAME TABLE evaluation_evaluator TO practical_submodule_processor;
RENAME TABLE evaluation_evaluator_simple TO practical_submodule_processor_simple;
RENAME TABLE evaluation_evaluator_sum_aggregate TO practical_submodule_processor_sum_aggregate;
RENAME TABLE evaluation_evaluator_product_aggregate TO practical_submodule_processor_product_aggregate;
RENAME TABLE evaluation_evaluator_sum_aggregate_evaluation_question TO practical_submodule_processor_sum_aggregate_question;
RENAME TABLE evaluation_evaluator_sum_aggregate_evaluation_evaluator TO practical_submodule_processor_sum_aggregate_processor;
RENAME TABLE evaluation_evaluator_product_aggregate_evaluation_question TO practical_submodule_processor_product_aggregate_question;
RENAME TABLE evaluation_evaluator_product_aggregate_evaluation_evaluator TO practical_submodule_processor_product_aggregate_processor;

ALTER TABLE practical_submodule_processor_sum_aggregate_question RENAME COLUMN evaluation_evaluator_sum_aggregate_id TO practical_submodule_processor_sum_aggregate_id;
ALTER TABLE practical_submodule_processor_sum_aggregate_question RENAME COLUMN evaluation_question_id TO practical_submodule_question_id;
ALTER TABLE practical_submodule_processor_product_aggregate_question RENAME COLUMN evaluation_evaluator_product_aggregate_id TO practical_submodule_processor_product_aggregate_id;
ALTER TABLE practical_submodule_processor_product_aggregate_question RENAME COLUMN evaluation_question_id TO practical_submodule_question_id;

ALTER TABLE practical_submodule_processor_sum_aggregate_processor RENAME COLUMN evaluation_evaluator_sum_aggregate_id TO practical_submodule_processor_sum_aggregate_id;
ALTER TABLE practical_submodule_processor_sum_aggregate_processor RENAME COLUMN evaluation_evaluator_id TO practical_submodule_processor_id;
ALTER TABLE practical_submodule_processor_product_aggregate_processor RENAME COLUMN evaluation_evaluator_product_aggregate_id TO practical_submodule_processor_product_aggregate_id;
ALTER TABLE practical_submodule_processor_product_aggregate_processor RENAME COLUMN evaluation_evaluator_id TO practical_submodule_processor_id;

ALTER TABLE practical_submodule_assessment_answer RENAME COLUMN evaluation_assessment_id TO practical_submodule_assessment_id;
ALTER TABLE practical_submodule_assessment_answer RENAME COLUMN evaluation_question_id TO practical_submodule_question_id;
ALTER TABLE practical_submodule_assessment_answer RENAME COLUMN evaluation_question_answer_id TO practical_submodule_question_answer_id;

ALTER TABLE practical_submodule_processor_simple RENAME COLUMN evaluation_evaluator_id TO practical_submodule_processor_id;
ALTER TABLE practical_submodule_processor_simple RENAME COLUMN evaluation_question_id TO practical_submodule_question_id;


/* PHASE 2 */
RENAME TABLE terms_of_service TO gdpr;
RENAME TABLE accepted_terms_of_service TO accepted_gdpr;
ALTER TABLE gdpr RENAME COLUMN content TO terms_of_service;
ALTER TABLE accepted_gdpr RENAME COLUMN terms_of_service_id TO gdpr_id;