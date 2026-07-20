# Entity Relationship Diagram

Rendered with Mermaid (GitHub renders this natively).

```mermaid
erDiagram
    users ||--o| alumni_profiles : "has one"
    users ||--o{ events : "created_by"
    users ||--o{ event_registrations : "registers"
    users ||--o{ activity_logs : "acts"
    users ||--o{ attendances : "checked_by"
    users ||--o{ notifications : "receives"

    events ||--o{ event_form_fields : "defines"
    events ||--o{ event_registrations : "receives"
    events ||--o{ sponsors : "sponsored by"

    event_registrations ||--o| payments : "paid via"
    event_registrations ||--o| tickets : "issues"
    event_registrations ||--o| attendances : "attended"

    roles ||--o{ model_has_roles : ""
    permissions ||--o{ role_has_permissions : ""

    users {
        bigint id PK
        string name
        string email UK
        string phone
        string password
        string status
        json notification_preferences
        timestamp deleted_at
    }
    alumni_profiles {
        bigint id PK
        bigint user_id FK,UK
        string student_id UK
        string batch
        string department
        string session
        string profession
        string company
        string designation
        string address
        string profile_photo
        text bio
    }
    events {
        bigint id PK
        string title
        string slug UK
        string banner
        text description
        string venue
        string type
        timestamp event_date
        timestamp registration_start
        timestamp registration_end
        decimal fee
        int max_capacity
        string status
        bigint created_by FK
    }
    event_form_fields {
        bigint id PK
        bigint event_id FK
        string label
        string name
        string type
        json options
        boolean is_required
        int sort_order
    }
    event_registrations {
        bigint id PK
        string registration_no UK
        bigint event_id FK
        bigint user_id FK
        string status
        string payment_status
        decimal amount
        json form_response
        timestamp registered_at
    }
    payments {
        bigint id PK
        bigint registration_id FK
        string transaction_id UK
        string gateway_transaction_id
        decimal amount
        string currency
        string gateway
        string status
        timestamp payment_date
        json meta
    }
    tickets {
        bigint id PK
        bigint registration_id FK,UK
        string ticket_no UK
        string qr_token UK
        string qr_signature
        string pdf_path
        timestamp checked_in_at
    }
    attendances {
        bigint id PK
        bigint registration_id FK,UK
        bigint event_id FK
        string status
        timestamp checkin_time
        timestamp checkout_time
        bigint checked_by FK
    }
    sponsors {
        bigint id PK
        bigint event_id FK
        string name
        string logo
        string website
        decimal amount
        string sponsor_type
        int sort_order
        boolean is_active
    }
    settings {
        bigint id PK
        string key UK
        json value
        string group
        boolean is_encrypted
        boolean is_public
    }
    activity_logs {
        bigint id PK
        bigint user_id FK
        string action
        string description
        string subject_type
        bigint subject_id
        json properties
        string ip_address
        timestamp created_at
    }
    notifications {
        uuid id PK
        string type
        string notifiable_type
        bigint notifiable_id
        text data
        timestamp read_at
    }
```

## Relationship summary

- **1 User ↔ 1 AlumniProfile** (unique `user_id`).
- **1 Event → N Registrations → (0..1 Payment, 0..1 Ticket, 0..1 Attendance)**.
- **1 Event → N FormFields** (dynamic registration form) and **N Sponsors**.
- **Users ↔ Roles ↔ Permissions** via Spatie pivot tables.
- **notifications** is polymorphic (`notifiable`), **activity_logs** has a
  polymorphic `subject`.
