-- M1.1 private proposal metadata only; never read by purchase/public-offer APIs.
ALTER TABLE tbl_owner_control_lab_store_drafts ADD COLUMN proposed_source_type TEXT NULL CHECK (proposed_source_type IS NULL OR proposed_source_type IN ('normal_store','genetic_item'));
ALTER TABLE tbl_owner_control_lab_store_drafts ADD COLUMN proposed_source_item_id INTEGER NULL CHECK (proposed_source_item_id IS NULL OR proposed_source_item_id > 0);
ALTER TABLE tbl_owner_control_lab_store_drafts ADD COLUMN proposed_lp_price INTEGER NULL CHECK (proposed_lp_price IS NULL OR proposed_lp_price >= 0);
ALTER TABLE tbl_owner_control_lab_store_drafts ADD COLUMN proposed_dspoinc_price INTEGER NULL CHECK (proposed_dspoinc_price IS NULL OR proposed_dspoinc_price >= 0);
ALTER TABLE tbl_owner_control_lab_store_drafts ADD COLUMN proposed_stock INTEGER NULL CHECK (proposed_stock IS NULL OR proposed_stock >= 0);
