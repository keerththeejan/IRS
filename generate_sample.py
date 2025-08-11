from openpyxl import Workbook

# Create a new workbook and select the active worksheet
wb = Workbook()
ws = wb.active

# Add headers
headers = ["Username", "Email", "Full Name", "Department", "Position"]
ws.append(headers)

# Add sample data
sample_users = [
    ["student1", "student1@example.com", "John Doe", "IT", "Student"],
    ["student2", "student2@example.com", "Jane Smith", "Engineering", "Student"],
    ["teacher1", "teacher1@example.com", "Robert Johnson", "IT", "Lecturer"],
    ["admin1", "admin1@example.com", "Admin User", "Administration", "Admin"]
]

for user in sample_users:
    ws.append(user)

# Save the file
wb.save("sample_users_import.xlsx")
print("Sample Excel file created: sample_users_import.xlsx")
