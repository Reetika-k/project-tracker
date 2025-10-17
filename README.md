README.md
#Project Tracker Plugin

Description
This is a custom WordPress plugin that allows admin users to manage projects via the WordPress dashboard and a custom REST API. It registers a custom post type for projects, a taxonomy for clients, custom meta fields, and provides API endpoints for CRUD operations.

Installation
1. Download the plugin zip file or clone the repository.
2. Upload the `project-tracker` folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

Usage

1.Dashboard Management
- Go to the WordPress admin dashboard.
- Navigate to "Projects" to add, edit, or delete projects.
- Assign clients via the "Clients" taxonomy.
- Use the "Project Details" meta box to set start/end dates, status, budget, and project manager.

2.REST API Usage
The API namespace is `/wp-json/project-tracker/v1/`.

- GET /projects: Retrieve all projects. Supports query params: `status` (active/on-hold/completed) and `client` (client slug).
  - Example: `GET /wp-json/project-tracker/v1/projects?status=active&client=client-a`
- POST /projects: Create a new project (requires authentication).
  - Body: JSON with `title`, `description`, `start_date`, `end_date`, `status`, `budget`, `project_manager`, `client`.
  - Example: `POST /wp-json/project-tracker/v1/projects` with JSON body.
- GET /projects/{id}: Get a single project.
  - Example: `GET /wp-json/project-tracker/v1/projects/123`
- PUT /projects/{id}: Update a project (requires authentication).
  - Body: JSON with fields to update.
- DELETE /projects/{id}: Delete a project (admins only).

3.Authentication: Use WordPress cookie authentication or JWT/OAuth for API calls requiring auth.

Assumptions
- Project managers are selected from users with 'administrator' or 'editor' roles.
- Clients are managed as a hierarchical taxonomy; assumes single client per project in API response.
- Status is limited to 'active', 'on-hold', 'completed'.
- No frontend display; this is backend/dashboard/API only.
- Caching is for the list endpoint only, expires in 60 seconds.
- Security: Relies on WordPress core capabilities; ensure proper user roles.

Development Notes
- Code follows WordPress PHP coding standards.
- All inputs are sanitized/validated.
- Nonces used for form submissions.
- Proper HTTP status codes returned in API errors.