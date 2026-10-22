# Manutenciona

#### Video Demo: https://youtu.be/W0ifdovAxUM

#### Description:

**Manutenciona** is a RESTful API for organizing maintenance requests in offices, schools, workplaces, and other shared facilities. It was developed as a CS50 final project and as a practical backend portfolio project using PHP and Laravel.

In many organizations, maintenance problems are reported through informal conversations or scattered messages. This makes it difficult to know which issues have been reported, who is responsible for them, and whether they have been resolved. Manutenciona addresses this problem by providing a central API through which users can submit requests and follow their progress, while administrators can assign responsible users and manage the request lifecycle.

The application intentionally focuses on the **backend**. It does not currently provide a dedicated graphical user interface; its features can be demonstrated with an HTTP client such as Insomnia or Postman. The API returns JSON responses and uses bearer tokens for protected endpoints.

## Features

- **User registration and authentication:** users can register, log in, and log out using Laravel Sanctum tokens. Public registration assigns the `requester` role.
- **Maintenance request creation:** authenticated users can submit a title, description, location, and optional priority. Requests start with the `open` status; when priority is omitted, it defaults to `medium`.
- **Role-based access:** requesters can list and view their own requests; administrators can access requests across users.
- **Responsible-user assignment:** administrators can assign or reassign an existing user to a maintenance request.
- **Controlled status updates:** administrators can advance or cancel requests according to explicit transition rules.
- **Audit history:** the API records the initial creation and subsequent assignment/status events, including the acting user and relevant details.
- **Transactional consistency:** operations that update a request and record its history are performed inside database transactions, so a failure while writing the history can roll back the related change.
- **Automated tests:** the project currently has 56 passing tests with 218 assertions, using Pest and Laravel's testing tools.

## Technologies

| Technology | Purpose |
| --- | --- |
| PHP `^8.3` | Server-side language |
| Laravel `^13.17` | API framework |
| MySQL | Database used by the project |
| Laravel Sanctum `^4.0` | API token authentication |
| Pest `4.7.8` | Automated tests |
| PHPUnit `^12.5.12` | Test infrastructure |
| Laravel Pint `^1.27` | PHP code formatting |
| Mockery `^1.6` | Mocking dependencies in tests |

Version numbers above are Composer constraints, not necessarily the exact versions installed on every machine. Consult `composer.lock` for resolved dependency versions.

## Architecture and Design Decisions

The code follows a layered approach:

```text
HTTP request
    ↓
Route
    ↓
Controller → FormRequest (validation)
    ↓
Service (authorization and business rules)
    ↓
Repository (data access)
    ↓
Eloquent Model / MySQL
    ↓
API Resource (JSON response)
```

Controllers coordinate requests and responses rather than holding the core business logic. FormRequests validate input, Services enforce permissions and status transitions, Repositories encapsulate persistence, Eloquent Models represent database records and relationships, and Resources format API output. Enums represent user roles, request statuses, and priorities.

A key design choice is to keep maintenance request updates and history entries in a **single database transaction**. For example, when an administrator changes a request from `open` to `in_progress`, the request status and its history entry must succeed together. If saving the history fails, the database change is rolled back. Automated tests simulate this failure to verify the behavior.

Another design choice is to prevent arbitrary status changes. A request cannot jump directly from `open` to `completed`, and terminal statuses cannot be reopened through the existing endpoint. These restrictions model a predictable maintenance workflow.

## Requirements

- PHP 8.3 or later, compatible with the project's Composer constraints
- Composer
- MySQL server and a database for the application
- PHP extensions required by Laravel and the selected database driver (including `pdo_mysql` for MySQL)
- Git (to clone the repository)
- An HTTP client such as Insomnia, Postman, or `curl` for testing API requests

Node.js and npm are only needed if you intend to build the Laravel frontend assets; the maintenance functionality documented here is API-based.

## Installation and Setup

1. Clone the repository and enter the project directory:

   ```bash
   git clone https://github.com/Laiendes/manutenciona.git
   cd manutenciona
   ```

2. Install PHP dependencies:

   ```bash
   composer install
   ```

3. Create the environment file:

   ```bash
   cp .env.example .env
   ```

   On Windows, you can run this command in Git Bash.

4. Create an empty MySQL database named `manutenciona` (or choose another name). Edit **your local `.env`** to match your environment:

   ```dotenv
   APP_NAME=Manutenciona
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=manutenciona
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   The database username and blank password above are **examples only**. Use your own MySQL credentials. Never commit your `.env` file or real secrets.

5. Generate an application key:

   ```bash
   php artisan key:generate
   ```

6. Run the database migrations:

   ```bash
   php artisan migrate
   ```

7. Start the development server:

   ```bash
   php artisan serve
   ```

   The API should now be accessible at `http://127.0.0.1:8000/api` (unless your server uses a different address or port).

**Administrator setup:** public registration creates `requester` accounts only. The project does not currently expose a public endpoint for promoting users to `admin`. An administrator must be provisioned through a trusted database/administrative workflow before testing administrator-only endpoints. Do not add `role: admin` to the registration payload and expect elevated privileges.

## Authentication

Register a new user:

**`POST /api/register`**

```json
{
  "name": "João Silva",
  "email": "joao@example.com",
  "password": "Senha12345",
  "password_confirmation": "Senha12345"
}
```

A successful registration returns **HTTP 201 Created**, with a JSON resource containing `user` and `token`. The example below is abbreviated; additional user attributes may be present:

```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com",
      "role": "requester"
    },
    "token": "<SANCTUM_TOKEN>"
  }
}
```

Passwords and remember tokens are hidden from serialized user responses, and passwords are stored using Laravel's hashed cast.

Log in using **`POST /api/login`** with the account credentials:

```json
{
  "email": "joao@example.com",
  "password": "Senha12345"
}
```

For protected routes, include these HTTP headers:

```http
Accept: application/json
Authorization: Bearer <SANCTUM_TOKEN>
```

Use **`POST /api/logout`** to revoke the current token. **`GET /api/user`** returns information about the authenticated user.

## API Endpoints

All endpoints use the `/api` prefix.

| Method | Endpoint | Description | Access |
| --- | --- | --- | --- |
| `POST` | `/api/register` | Register a new requester | Public |
| `POST` | `/api/login` | Authenticate and receive a token | Public |
| `POST` | `/api/logout` | Revoke the current token | Authenticated |
| `GET` | `/api/user` | View authenticated user | Authenticated |
| `POST` | `/api/maintenance-requests` | Create a maintenance request | Authenticated |
| `GET` | `/api/maintenance-requests` | List accessible requests | Authenticated |
| `GET` | `/api/maintenance-requests/{id}` | View an accessible request | Authenticated |
| `PATCH` | `/api/maintenance-requests/{id}/assign` | Assign or reassign a responsible user | Administrator |
| `GET` | `/api/maintenance-requests/{id}/history` | View the request's history | Authorized requester or administrator |
| `PATCH` | `/api/maintenance-requests/{id}/status` | Update a request's status | Administrator |

### Create a Maintenance Request

**`POST /api/maintenance-requests`**

```json
{
  "title": "Air conditioner not working",
  "description": "The equipment is not cooling the room.",
  "location": "Room 203",
  "priority": "high"
}
```

Validation rules:

- `title`: required string, maximum 255 characters.
- `description`: required string.
- `location`: required string, maximum 255 characters.
- `priority`: optional; must be a valid value from the priority enum. The default is `medium`.

The API determines `requester_id` from the authenticated user, rather than accepting an arbitrary requester from the client. Newly created requests start as `open`. Successful creation returns **HTTP 201 Created**.

### Assign a Responsible User

**`PATCH /api/maintenance-requests/1/assign`** — administrator only:

```json
{
  "assignee_id": 2
}
```

`assignee_id` must be an integer referencing an existing user. Assignments and reassignments are recorded in the request history.

### Update a Request Status

**`PATCH /api/maintenance-requests/1/status`** — administrator only:

```json
{
  "status": "in_progress"
}
```

The API enforces the following transitions:

| Current status | Allowed next statuses |
| --- | --- |
| `open` | `in_progress`, `cancelled` |
| `in_progress` | `completed`, `cancelled` |
| `completed` | None (terminal) |
| `cancelled` | None (terminal) |

Changing to the same status is not allowed. Invalid values and forbidden transitions return **HTTP 422 Unprocessable Entity**. Authorized updates record the old and new statuses in the history.

### View Request History

**`GET /api/maintenance-requests/1/history`**

The history endpoint returns the recorded events for an accessible request in chronological order. Requesters may access the history of their own requests; administrators may access histories across users.

## Permissions and Error Handling

- **Requester:** can create requests and list/view their own requests and histories; cannot assign responsible users or change statuses.
- **Administrator:** can list/view requests across users, assign responsible users, and update request statuses.
- **Unauthenticated requests:** protected endpoints return **HTTP 401 Unauthorized**.
- **Forbidden operations:** return **HTTP 403 Forbidden**.
- **Nonexistent requests:** return **HTTP 404 Not Found**.
- **Invalid request data or status transitions:** return **HTTP 422 Unprocessable Entity**.

## Testing and Code Quality

Run the full test suite:

```bash
php artisan test
```

At the time of writing, the project has **56 passing tests and 218 assertions**. Tests cover authentication, request creation, list/show permissions, history access and ordering, assignment, valid and invalid status transitions, and transactional rollback when writing history fails.

Run Laravel Pint to format the code:

```bash
vendor/bin/pint
```

On Windows, the equivalent command used during development is:

```bat
vendor\bin\pint
```

The tests use Laravel's testing facilities, Pest, and Mockery. Use a dedicated test database/configuration appropriate for your environment before running tests against a database containing valuable data.

## Project Structure

```text
app/
├── Enums/                  # Roles, statuses, and priorities
├── Http/
│   ├── Controllers/Api/    # API endpoints
│   ├── Requests/           # Request validation
│   └── Resources/          # JSON response formatting
├── Models/                 # Eloquent models and relationships
├── Repositories/           # Database access
└── Services/               # Business logic

database/migrations/        # Database schema
routes/api.php              # API route definitions
tests/Feature/Api/          # API feature tests
```

## Demonstrating the Project

A simple demonstration using an HTTP client can show the complete workflow:

1. Register a requester and receive a Sanctum token.
2. Create a maintenance request and inspect its initial `open` status.
3. List requests and open the request history.
4. Authenticate as a pre-provisioned administrator.
5. Assign a responsible user to the request.
6. Change the status from `open` to `in_progress`, then to `completed`.
7. Inspect the history to show the sequence of actions.
8. Attempt an invalid transition to demonstrate validation.
9. Run the automated tests to demonstrate the project's reliability.

## CS50 Final Project Notes

Manutenciona was designed to demonstrate backend development concepts through a complete, testable workflow rather than a collection of unrelated endpoints. The main challenge was coordinating authentication, access control, request lifecycle rules, data persistence, and audit history so they behave consistently.

The project separates HTTP concerns from business rules to keep the code easier to understand and extend. The Service layer decides whether a user can perform an action and whether a status transition is valid; Repositories handle database operations; Resources control the JSON representation. This design also makes it practical to test behavior independently of the user interface.

The history feature is especially important: maintenance requests are not just records with a current status. They represent a sequence of decisions and changes. By recording those changes, the system makes it possible to understand how a request progressed and who acted on it. Database transactions ensure that an update does not silently succeed without its corresponding history entry.

The scope is deliberately limited to a REST API. A future version could add a web dashboard, notifications, filtering, attachments, and more detailed assignment policies. Those enhancements are **not part of the current implementation** and are not required to use the existing endpoints.

## License

The project is currently based on the Laravel application skeleton, whose Composer metadata declares the MIT license. Confirm and add an appropriate project-level `LICENSE` file before making a definitive licensing claim about your own project code.
