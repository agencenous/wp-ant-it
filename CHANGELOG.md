# WP Ant IT changelog

## 0.3.2

- Do not erase data with `fill_with` if data is not provided

## 0.3.1

- Fix type check in fill_with method to ensure value is a string before unserializing

## 0.3.0

- Adds upgrade manager

## 0.2.0

- Allows to pass JOIN and custom WHERE params to get() method
- Declare return type of Model methods

## 0.1.0

- Add methods to retrieve SQL table prefix and process SQL file content
- Expose SQL schema version information through the manager
- Add versioned, one-time manager upgrade callbacks


## 0.0.8

- Add `%WP_DB_PREFIX%` and `%WP_DB_NAME%` placeholders

## 0.0.7

- Fix permission check

## 0.0.6

- Show upgrade errors in admin notices

## 0.0.5

- Do not update table version if update fails
- Show update failures in site-health and mention then as critical

## 0.0.4

- Explicit token for tablename replacement in SQL instructions
- Add site health infos
- Remove mentions about the ability to directly store SQL queries in variables

## 0.0.3

- Comment methods
- Add Changelog

## 0.0.2

- Add README instructions
- Declare composer library

## 0.0.1

- Initial code base
- Add Manager, Model and Queen classes