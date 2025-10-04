# Member Engagement API

## Overview
The Member Engagement API provides comprehensive statistics about a member's engagement with the organization, including mission participation, learning progress, prayer activity, and overall impact.

## Endpoint

### Get Member Engagement Statistics
```
GET /api/v1/members/{member_ulid}/engagement
```

Retrieves detailed engagement statistics for a specific member.

## Authentication
This endpoint requires authentication via Sanctum token.

## Parameters

### Path Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| member_ulid | string | The unique ULID of the member |

### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| include_badges | boolean | No | false | Include achievement badges in the response |
| include_comparative_stats | boolean | No | false | Include comparative statistics vs community averages |
| year | integer | No | null | Filter engagement data by specific year (e.g., 2024) |

## Response Structure

### Success Response (200 OK)
```json
{
  "data": {
    "entity": "member-engagement",
    "member_ulid": "01HX...",
    "member_name": "John Doe",
    "mission_stats": {
      "total_missions": 15,
      "approved_missions": 12,
      "mission_streak": 5,
      "favorite_mission_type": {
        "id": 1,
        "name": "School Evangelism"
      },
      "schools_reached": 8,
      "mission_roles": [
        {
          "role": 1,
          "count": 10
        }
      ],
      "completion_rate": 80.00
    },
    "impact_stats": {
      "souls_touched": 156,
      "decision_types": [
        {
          "type": "Salvation",
          "count": 89
        },
        {
          "type": "Rededication",
          "count": 67
        }
      ],
      "most_impactful_mission": {
        "mission_ulid": "01HX...",
        "theme": "Transform Lives",
        "school_name": "ABC High School",
        "souls_count": 45
      }
    },
    "learning_stats": {
      "courses_completed": 3,
      "total_courses_enrolled": 5,
      "lessons_completed": 28,
      "learning_progress_percentage": 65.50,
      "learning_streak": 7,
      "favorite_course": {
        "ulid": "01HX...",
        "name": "Evangelism Basics",
        "progress": 95.00
      }
    },
    "prayer_stats": {
      "prayer_responses": 42,
      "prayer_consistency_days": 28
    },
    "event_stats": {
      "events_attended": 8,
      "upcoming_events": 2
    },
    "generated_at": "2024-10-04T12:00:00+00:00"
  }
}
```

### With Badges (include_badges=true)
Additional `badges` array will be included with achievement information.

### With Comparative Stats (include_comparative_stats=true)
Additional `comparative_stats` object will be included with community comparison data.

## Field Descriptions

### Mission Stats
- **total_missions**: Total number of mission subscriptions
- **approved_missions**: Number of approved mission subscriptions
- **mission_streak**: Longest consecutive missions attended
- **favorite_mission_type**: Most frequently joined mission type
- **schools_reached**: Number of unique schools visited
- **mission_roles**: Distribution of different roles taken in missions
- **completion_rate**: Percentage of missions approved vs total subscribed

### Impact Stats
- **souls_touched**: Total number of souls recorded during member's missions
- **decision_types**: Breakdown of soul decision types (Salvation, Rededication, etc.)
- **most_impactful_mission**: Mission where member helped record the most souls

### Learning Stats
- **courses_completed**: Number of courses fully completed
- **total_courses_enrolled**: Total number of courses enrolled
- **lessons_completed**: Total lessons completed across all courses
- **learning_progress_percentage**: Average completion percentage across all courses
- **learning_streak**: Consecutive days with lesson completions
- **favorite_course**: Course with highest progress or completion

### Prayer Stats
- **prayer_responses**: Total prayer prompts responded to
- **prayer_consistency_days**: Unique days with prayer responses

### Event Stats
- **events_attended**: Total events subscribed to
- **upcoming_events**: Number of upcoming event subscriptions

## Badge Criteria
- **Mission Veteran**: 10+ approved missions
- **Soul Winner**: 50+ souls touched
- **Learning Champion**: 3+ courses completed
- **Prayer Warrior**: 30+ prayer responses
- **School Explorer**: 5+ different schools visited
- **Faithful Servant**: 5+ mission streak

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 404 Not Found
```json
{
  "message": "No query results for model [App\\Models\\Member] {ulid}"
}
```

### 422 Unprocessable Entity (Invalid Parameters)
```json
{
  "message": "The year field must be at least 2020.",
  "errors": {
    "year": [
      "The year field must be at least 2020."
    ]
  }
}
```

## Example Requests

### Basic Request
```bash
curl -X GET \
  'https://api.example.com/api/v1/members/01HX.../engagement' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/json'
```

### Request with Badges
```bash
curl -X GET \
  'https://api.example.com/api/v1/members/01HX.../engagement?include_badges=true' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/json'
```

### Request for Specific Year
```bash
curl -X GET \
  'https://api.example.com/api/v1/members/01HX.../engagement?year=2024' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/json'
```

### Request with All Options
```bash
curl -X GET \
  'https://api.example.com/api/v1/members/01HX.../engagement?include_badges=true&include_comparative_stats=true&year=2024' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/json'
```

## Use Cases

### Mobile App "Wrapped" Feature
This endpoint can be used to generate an Instagram-story-like "wrapped" experience showing members their yearly engagement statistics with achievement badges and comparative insights.

### Personal Dashboard
Display member engagement metrics on a personal dashboard to encourage continued participation and highlight areas of growth.

### Ministry Analytics
Aggregate engagement data across members to identify highly engaged members, popular mission types, and areas for improvement.

## Notes
- All date/time values are in ISO 8601 format
- Streak calculations allow for reasonable gaps (e.g., 90 days for missions, 2 days for learning)
- Comparative statistics are calculated against all active members in the system
- Year filtering applies to the `created_at` timestamp of engagement records
