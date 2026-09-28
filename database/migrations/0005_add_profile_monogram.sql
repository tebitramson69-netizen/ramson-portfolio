-- The fallback monogram is a brand decision, not something to derive from the
-- name. Deriving first+last initials from "Tebit Ramson Titih" gives "TT",
-- but the approved mark is "RT". Storing it makes it editable from the CMS
-- like every other piece of identity, instead of being a rule in code that
-- has to be argued with.
--
-- Empty means "derive from the name", so the column is never a trap for a
-- future profile that has no explicit mark.

ALTER TABLE profile
    ADD COLUMN monogram VARCHAR(4) NOT NULL DEFAULT '' AFTER full_name;
