# Text-Roleplay API Documentation

This API provides programmatic access to the Text-Roleplay platform, allowing for mobile and third-party integrations.

## Authentication

The API uses **Laravel Sanctum** for authentication. All protected routes require a Bearer token in the `Authorization` header.

```http
Authorization: Bearer <your-token>
```

### Registration
`POST /api/register`

Creates a new user account and returns an API token.

**Request Body:**
| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `name` | string | Yes | User's full name |
| `email` | string | Yes | User's email address |
| `password` | string | Yes | User's password (min 8 chars) |
| `password_confirmation` | string | Yes | Must match `password` |
| `device_name` | string | Yes | A label for the token |

**Response:**
```json
{
    "token": "1|ABC123xyz...",
    "user": {
        "id": 1,
        "name": "Faisal",
        "email": "test@example.com",
        "avatar": "https://ui-avatars.com/api/?name=Faisal&color=7F9CF5&background=EBF4FF"
    }
}
```

### User Profile
`GET /api/user` (Protected)

Returns the authenticated user's profile information.

**Response:**
```json
{
    "data": {
        "id": 1,
        "name": "Faisal",
        "email": "test@example.com",
        "avatar": "https://ui-avatars.com/api/?name=Faisal&color=7F9CF5&background=EBF4FF",
        "created_at": "2026-02-03T09:00:00.000000Z"
    }
}
```

### Login
`POST /api/login`

Authenticates a user and returns an API token.

**Request Body:**
| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `email` | string | Yes | User's email address |
| `password` | string | Yes | User's password |
| `device_name` | string | Yes | A label for the token (e.g., "iPhone 15") |

**Response:**
```json
{
    "token": "1|ABC123xyz...",
    "user": {
        "id": 1,
        "name": "Faisal",
        "email": "test@example.com"
    }
}
```

### Logout
`POST /api/logout` (Protected)

Revokes the current access token.

**Response:**
```json
{
    "message": "Logged out successfully"
}
```

---

## LLM Models

### List Available Models
`GET /api/llm-models` (Protected)

Returns a list of active LLM models that can be used for gameplay.

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Gemini 1.5 Flash",
            "identifier": "gemini-1.5-flash",
            "provider": {
                "id": 1,
                "name": "Google AI Studio",
                "slug": "google-ai-studio"
            },
            "is_active": true
        }
    ]
}
```

---

---

## Subscriptions

### List Plans
`GET /api/subscriptions` (Protected)

Returns available subscription plans and the user's current subscription status.

**Response:**
```json
{
    "plans": [
        {
            "id": 1,
            "name": "Pro Plan",
            "slug": "pro-monthly",
            "price": 50000,
            "currency": "IDR",
            "billing_period": "monthly",
            "max_tokens": 100000,
            "is_active": true
        }
    ],
    "current_subscription": {
        "id": 5,
        "status": "active",
        "starts_at": "2024-03-01T10:00:00.000000Z",
        "expires_at": "2024-04-01T10:00:00.000000Z"
    }
}
```

### Subscribe
`POST /api/subscriptions` (Protected)

Creates a new subscription for the user and returns a payment invoice.

**Request Body:**
```json
{
    "subscription_plan_id": 1
}
```

**Response:**
```json
{
    "message": "Subscription created successfully. Please proceed to payment.",
    "invoice_url": "https://checkout.xendit.co/web/65e...",
    "expires_at": "2024-03-02T10:05:00.000Z"
}
```

## Games

### List Games
`GET /api/games` (Protected)

Returns a list of all available games.

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "title": "Neon Shadows",
            "description": "A cyberpunk noir detective story.",
            "settings": {
                "llm_enabled": true,
                "default_mode": "standard"
            },
            "created_at": "...",
            "updated_at": "..."
        }
    ]
}
```

### Get Game Details
`GET /api/games/{id}` (Protected)

Returns detailed information about a specific game, including its story nodes and choices.

---

## Gameplay Sessions

### List Sessions
`GET /api/games/{game_id}/play` (Protected)

Returns all gameplay sessions for the authenticated user and specified game.

### Start New Session
`POST /api/games/{game_id}/play` (Protected)

Creates a new adventure session starting at the game's designated start node.

### Get Session State
`GET /api/games/{game_id}/play/{session_id}` (Protected)

Returns the current state of a session, including the current story node and content.

**Response Body Snippet:**
```json
{
    "data": {
        "id": 1,
        "mode": "standard",
        "current_node": {
            "id": 5,
            "content": "You see a flickering light at the end of the hall...",
            "choices": [
                { "id": 10, "label": "Investigate light", "target_node_id": 6 },
                { "id": 11, "label": "Turn back", "target_node_id": 4 }
            ]
        }
    }
}
```

---

## Gameplay Actions

### Perform Action
`POST /api/games/{game_id}/play/{session_id}/action` (Protected)

The primary endpoint for interacting with the game.

**Request Body (Standard Choice):**
```json
{
    "action_type": "choice",
    "choice_id": 10
}
```

**Request Body (LLM Text Input - requires LLM mode):**
```json
{
    "action_type": "text",
    "input_text": "I try to pick the lock on the door."
}
```

**Response:**
Returns the updated `GameSessionResource` with the new story state.

### Switch Gameplay Mode
`POST /api/games/{game_id}/play/{session_id}/switch-mode` (Protected)

Toggles between `standard` and `llm` modes.

**Request Body:**
```json
{
    "mode": "llm" 
}
```

### Restart Adventure
`POST /api/games/{game_id}/play/{session_id}/restart` (Protected)

Resets the session to the game's start node and clears all history.

### Regenerate Response
`POST /api/games/{game_id}/play/{session_id}/regenerate` (Protected)

Regenerates the last AI response. Only available if `allow_llm_regeneration` is enabled in game settings and the session is in `llm` mode.

**Request Body:**
```json
{
    "model": "gemini-1.5-flash" 
}
```

**Response:**
Returns the updated `GameSessionResource`.

### Edit Response
`POST /api/games/{game_id}/play/{session_id}/edit-response` (Protected)

Manually edits a specific AI response in the session history.

**Request Body:**
| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `history_id` | integer | Yes | The ID of the history entry to edit |
| `content` | string | Yes | The new content for the response |

**Response:**
Returns the updated `GameSessionResource`.

---

## History

### Chat History
`GET /api/games/{game_id}/play/{session_id}/history` (Protected)

Returns the full chat history for a specific game session.

**Response:**
```json
{
    "data": [
        {
            "id": 101,
            "role": "assistant",
            "content": "You see a flickering light...",
            "created_at": "2026-02-03T10:00:00.000000Z"
        },
        {
            "id": 102,
            "role": "user",
            "content": "Investigate light",
            "created_at": "2026-02-03T10:01:00.000000Z"
        }
    ]
}
```

### Redeem History
`GET /api/redeem/history` (Protected)

Returns a list of redeeming codes used by the user.

**Response:**
```json
{
    "data": [
        {
            "id": 5,
            "code": "WELCOME2026",
            "llm_model": "Gemini 1.5 Flash",
            "period": "monthly",
            "max_tokens": 100000,
            "redeemed_at": "2026-02-01T12:00:00.000000Z"
        }
    ]
}
```
