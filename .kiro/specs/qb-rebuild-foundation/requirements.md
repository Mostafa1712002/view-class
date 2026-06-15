# Requirements: Question-Bank Rebuild — Foundation

## Overview
Foundation (schema + taxonomy + module structure + permissions) for the question-bank
rebuild per Trello #258 (proposed DB), #247 (rebuild overview), #248 (taxonomy layer).
Additive and non-destructive: the live `admin/question-banks` feature must keep working.
Question-type screens (#250–#256) are out of scope.

## User Stories

### US-001: Additive schema for the new QB model
**As a** platform developer
**I want** the #258 schema available as new tables / nullable columns
**So that** the rebuild screens can be built without breaking the live question bank.

**Acceptance Criteria:**
- WHEN migrations run THE SYSTEM SHALL add new tables and nullable/defaulted columns only.
- THE SYSTEM SHALL NOT drop, rename, or alter existing question-bank columns.
- WHEN the migration completes THE SYSTEM SHALL leave existing QB pages fully functional.

### US-002: Educational taxonomy layer (#248)
**As a** school admin
**I want** compounds, skills, standards, weeks, and semesters represented as data
**So that** questions can be classified before they are entered.

**Acceptance Criteria:**
- THE SYSTEM SHALL reuse academic_terms (semester), study_weeks (week), domains (domain).
- THE SYSTEM SHALL provide new compounds, skills, skill_assignments, standards entities.
- WHERE grade-level is referenced THE SYSTEM SHALL store a loose grade id (no FK to the gradebook).

### US-003: Permission keys for the rebuild
**As a** super-admin
**I want** permission keys for the new taxonomy modules in the matrix
**So that** access can be granted per job title.

**Acceptance Criteria:**
- THE SYSTEM SHALL seed compounds/skills/weeks/standards permission keys idempotently.
- WHEN the permission matrix loads THE SYSTEM SHALL show the four new groups.

## Non-Functional Requirements
### NFR-001: Multi-tenant scope
- School-scoped taxonomy queries SHALL go through repositories and respect scopedSchoolId() fail-closed semantics.
### NFR-002: Soft deletes
- New tenant-owned entities (compounds, skills, standards, passages) SHALL use soft deletes.
