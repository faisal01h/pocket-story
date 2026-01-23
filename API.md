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
        "email": "test@example.com"
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
