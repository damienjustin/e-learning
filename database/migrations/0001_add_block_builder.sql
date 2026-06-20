ALTER TABLE lessons ADD COLUMN content_blocks LONGTEXT DEFAULT NULL AFTER content;
ALTER TABLE courses ADD COLUMN description_blocks LONGTEXT DEFAULT NULL AFTER description;
