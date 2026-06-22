import re

with open('d:/git-PRMS Dev/PRMS_Dev/prms/database/prms_dump_utf8.sql', 'r', encoding='utf-8') as f:
    content = f.read()

# Find all tables
tables = re.findall(r'CREATE TABLE `([^`]+)` \((.*?)\) ENGINE=', content, re.DOTALL)
print(f'Total tables found: {len(tables)}')

for table_name, table_schema in tables:
    print(f'\n--- Table: {table_name} ---')
    # Count rows by finding INSERT INTO
    inserts = re.findall(rf'INSERT INTO `{table_name}` VALUES (.*);', content)
    row_count = 0
    if inserts:
        for insert in inserts:
            # roughly count number of tuples (...,...), (...) by counting the start parentheses not inside quotes
            # A simpler way: count the number of "),(" and add 1
            row_count += insert.count('),(') + 1
    print(f'Estimated Rows in Dump: {row_count}')
    
    # Extract foreign keys
    fks = re.findall(r'CONSTRAINT `[^`]+` FOREIGN KEY \(`([^`]+)`\) REFERENCES `([^`]+)` \(`([^`]+)`\)', table_schema)
    if fks:
        print('Foreign Keys:')
        for fk in fks:
            print(f'  {fk[0]} -> {fk[1]}({fk[2]})')
    else:
        print('Foreign Keys: None')

    # Look for unique keys or indices to identify potential relationships
    keys = re.findall(r'KEY `[^`]+` \(`([^`]+)`\)', table_schema)
    unique_keys = re.findall(r'UNIQUE KEY `[^`]+` \(`([^`]+)`\)', table_schema)
    if keys:
        print(f'Indices on: {", ".join(keys)}')
    if unique_keys:
        print(f'Unique Keys on: {", ".join(unique_keys)}')
