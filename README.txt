RENDER SETUP
1. Neon (free database): neon.tech -> create project -> copy the connection string (postgresql://...).
2. GitHub: create a repo and upload ALL files in this folder (Dockerfile at the top level).
3. Render: New -> Web Service -> pick the repo -> Language: Docker -> Instance: Free.
4. Environment variables:  DATABASE_URL = (Neon string)   ADMIN_PASSWORD = (your chosen password)
5. Deploy. Invitation: your-app.onrender.com   Admin: your-app.onrender.com/admin.php
