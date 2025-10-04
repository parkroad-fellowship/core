# Member Engagement Tracking - Complete Documentation

## Table of Contents
1. [Overview](#overview)
2. [Implementation Summary](#implementation-summary)
3. [API Documentation](#api-documentation)
4. [Statistics & Features](#statistics--features)
5. [Architecture & Technical Details](#architecture--technical-details)
6. [Future Enhancements](#future-enhancements)

---

## Overview

The Member Engagement Tracking system provides comprehensive statistics about a member's engagement with the organization, including mission participation, learning progress, prayer activity, and overall impact. This creates an engaging "Missions Wrapped" experience similar to Spotify Wrapped, celebrating member contributions while encouraging continued participation.

---

## Implementation Summary

### What Was Implemented

#### 1. API Endpoint
**Route:** `GET /api/v1/members/{member_ulid}/engagement`

This endpoint allows mobile apps and dashboards to retrieve detailed engagement statistics for any member.

#### 2. Key Features
- ✅ **Mission Participation Stats**: Total missions, streaks, favorite types, schools reached, roles, completion rates
- ✅ **Impact Metrics**: Souls touched, decision types breakdown, most impactful mission
- ✅ **Learning Progress**: Courses completed, lessons mastered, learning streaks
- ✅ **Prayer Engagement**: Response counts, consistency tracking
- ✅ **Event Participation**: Attended and upcoming events
- ✅ **Achievement Badges**: 6 different badges based on engagement levels
- ✅ **Comparative Stats**: Member performance vs community averages
- ✅ **Year Filtering**: View engagement for specific years

#### 3. Architecture Pattern
Follows the established repository patterns:
- **Controller** → Uses sync jobs (like `PaymentInstructionController`)
- **Form Request** → Validates query parameters
- **Resource** → Formats API response consistently
- **Job** → Encapsulates business logic for calculating statistics
- **Tests** → Comprehensive Pest tests covering all scenarios

### Files Created

```
app/
├── Http/
│   ├── Controllers/API/
│   │   └── MemberEngagementController.php          # API endpoint controller
│   ├── Requests/MemberEngagement/
│   │   └── GetEngagementRequest.php                # Request validation
│   └── Resources/MemberEngagement/
│       └── Resource.php                            # Response formatting
├── Jobs/MemberEngagement/
│   └── GetEngagementJob.php                        # Business logic
tests/Unit/
└── MemberEngagementTest.php                        # Comprehensive tests
routes/api/
└── v1.php                                          # Route registration (modified)
```

---

## API Documentation

### Endpoint

#### Get Member Engagement Statistics
```
GET /api/v1/members/{member_ulid}/engagement
```

Retrieves detailed engagement statistics for a specific member.

### Authentication
This endpoint requires authentication via Sanctum token.

### Parameters

#### Path Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| member_ulid | string | The unique ULID of the member |

#### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| include_badges | boolean | No | false | Include achievement badges in the response |
| include_comparative_stats | boolean | No | false | Include comparative statistics vs community averages |
| year | integer | No | null | Filter engagement data by specific year (e.g., 2024) |

### Response Structure

#### Success Response (200 OK)
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

#### With Badges (include_badges=true)
Additional `badges` array will be included with achievement information.

#### With Comparative Stats (include_comparative_stats=true)
Additional `comparative_stats` object will be included with community comparison data.

### Field Descriptions

#### Mission Stats
- **total_missions**: Total number of mission subscriptions
- **approved_missions**: Number of approved mission subscriptions
- **mission_streak**: Longest consecutive missions attended
- **favorite_mission_type**: Most frequently joined mission type
- **schools_reached**: Number of unique schools visited
- **mission_roles**: Distribution of different roles taken in missions
- **completion_rate**: Percentage of missions approved vs total subscribed

#### Impact Stats
- **souls_touched**: Total number of souls recorded during member's missions
- **decision_types**: Breakdown of soul decision types (Salvation, Rededication, etc.)
- **most_impactful_mission**: Mission where member helped record the most souls

#### Learning Stats
- **courses_completed**: Number of courses fully completed
- **total_courses_enrolled**: Total number of courses enrolled
- **lessons_completed**: Total lessons completed across all courses
- **learning_progress_percentage**: Average completion percentage across all courses
- **learning_streak**: Consecutive days with lesson completions
- **favorite_course**: Course with highest progress or completion

#### Prayer Stats
- **prayer_responses**: Total prayer prompts responded to
- **prayer_consistency_days**: Unique days with prayer responses

#### Event Stats
- **events_attended**: Total events subscribed to
- **upcoming_events**: Number of upcoming event subscriptions

### Error Responses

#### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

#### 404 Not Found
```json
{
  "message": "No query results for model [App\\Models\\Member] {ulid}"
}
```

#### 422 Unprocessable Entity (Invalid Parameters)
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

### Example Requests

#### Basic Request
```bash
curl -X GET \
  'https://api.example.com/api/v1/members/01HX.../engagement' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/json'
```

#### Request with Badges
```bash
curl -X GET \
  'https://api.example.com/api/v1/members/01HX.../engagement?include_badges=true' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/json'
```

#### Request for Specific Year
```bash
curl -X GET \
  'https://api.example.com/api/v1/members/01HX.../engagement?year=2024' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/json'
```

#### Request with All Options
```bash
curl -X GET \
  'https://api.example.com/api/v1/members/01HX.../engagement?include_badges=true&include_comparative_stats=true&year=2024' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/json'
```

---

## Statistics & Features

### 🎯 Mission Participation Stats

#### Core Mission Metrics
- **Total Missions Participated**: Count of approved mission subscriptions
- **Mission Streak**: Longest consecutive missions attended (allows up to 90 days gap)
- **Favorite Mission Type**: Most frequently joined mission type
- **Schools Reached**: Number of unique schools visited
- **Mission Role Distribution**: Breakdown of different roles taken (from mission_role in MissionSubscription)
- **Mission Completion Rate**: Percentage of missions actually attended vs. subscribed to

#### Impact & Souls
- **Lives Touched**: Total number of souls recorded during missions they participated in
- **Decision Types Witnessed**: Breakdown of soul decision types they were part of
- **Most Impactful Mission**: Mission where they helped record the most souls

### 📚 Learning & Development

#### Course Engagement
- **Courses Completed**: Number of courses finished (from CourseMember)
- **Learning Progress**: Average completion percentage across all courses
- **Lessons Mastered**: Total lessons completed (from LessonMember)
- **Learning Streak**: Consecutive days of lesson completion (allows up to 2 days gap)
- **Favorite Course**: Most engaged course based on completion time

### 🙏 Spiritual Engagement

#### Prayer Life
- **Prayer Responses**: Total prayer prompts responded to
- **Prayer Consistency**: Unique days with prayer responses
- **Prayer Requests Made**: Number of prayer requests submitted

### 💰 Financial Stewardship

#### Mission Expenses (if applicable)
- **Missions Supported Financially**: Count of missions with expense contributions
- **Total Contribution**: Sum of financial contributions to mission expenses

### 🏆 Recognition & Achievements

#### Achievement Badges

| Badge | Criteria | Icon |
|-------|----------|------|
| Mission Veteran | 10+ approved missions | 🎖️ |
| Soul Winner | 50+ souls touched | 👑 |
| Learning Champion | 3+ courses completed | 📚 |
| Prayer Warrior | 30+ prayer responses | 🙏 |
| School Explorer | 5+ different schools visited | 🗺️ |
| Faithful Servant | 5+ mission streak | ⭐ |

#### Unique Achievements (Future Enhancement)
- **Early Bird**: First to subscribe to missions
- **Team Player**: Most collaborative mission participant
- **Debrief Contributor**: Contributed to mission debrief notes
- **Multi-Role Master**: Served in multiple mission roles

### 📊 Comparative Stats

#### Personal Growth
- **This Term vs Last Term**: Comparison of key metrics
- **Monthly Progression**: Month-by-month participation trends
- **Goal Achievement**: Progress toward personal participation goals

#### Community Context
- **Rank Among Peers**: Position in participation leaderboard (sensitively presented)
- **Above Average In**: Areas where they exceed community average

### 🎨 Visual Elements (Mobile App Integration)

#### Interactive Features
- **Mission Map**: Visual map showing all schools visited
- **Timeline**: Chronological view of their term activities
- **Progress Rings**: Circular progress indicators for different metrics
- **Photo Memories**: Collage of mission photos they were part of

#### Encouraging Messages
- **Personal Highlights**: "Your biggest impact was..."
- **Growth Moments**: "You grew the most in..."
- **Future Potential**: "Next term, you could..."

### 🔮 Predictive Insights (Future Enhancement)
- **Suggested Next Steps**: Recommended courses or mission types based on their pattern
- **Potential Impact**: Projected souls they could reach next term
- **Skill Development**: Areas for growth based on their participation history

---

## Architecture & Technical Details

### Testing

Comprehensive test coverage includes:
- ✅ Basic engagement statistics retrieval
- ✅ Badge inclusion when requested
- ✅ Comparative stats when requested
- ✅ Year filtering
- ✅ 404 handling for non-existent members
- ✅ Mission stats calculation validation
- ✅ Impact stats with souls data
- ✅ Authentication requirement

Run tests with:
```bash
php artisan test --filter=MemberEngagementTest
```

### Technical Implementation Details

#### Streak Calculation
- **Mission Streak**: Allows up to 90 days gap between missions
- **Learning Streak**: Allows up to 2 days gap between lesson completions

#### Performance Considerations
- Uses eager loading to minimize database queries
- Calculates stats efficiently using query builder where possible
- Caches relationships on the member model

#### Data Filtering
- Year filtering applies to `created_at` timestamps
- Only counts approved mission subscriptions for mission stats
- Only counts completed courses/lessons for learning stats

### Mobile App Integration

This endpoint is designed for mobile apps to create engaging "wrapped" experiences:

1. **End of Year Summary**: Show members their yearly achievements
2. **Progress Tracking**: Display ongoing engagement metrics
3. **Gamification**: Use badges to encourage participation
4. **Social Sharing**: Members can share their wrapped stats
5. **Goal Setting**: Compare against community averages

---

## Future Enhancements

### Planned Data Models (Not Yet Implemented)

The following models and migrations are planned for future implementation to support enhanced features:

#### MemberTermSummary Model
```php
// Flexible summary model supporting term, year, and custom periods
// Fields: missions_participated, souls_recorded, courses_completed, etc.
// Scopes: forTerm(), forYear(), forCustomPeriod()
```

#### MemberYearSummary Model
```php
// Aggregates data across all terms in a year
// Links to individual term summaries
// Calculates: growth_percentage, consistency_score, year_best_streak
```

#### MemberAchievement Model
```php
// Stores earned achievements with flexible period types
// Supports term, year, and milestone achievements
// Tracks: achievement_type, earned_at, is_yearly_achievement
```

#### MemberMissionImpact Model
```php
// Detailed mission-level impact tracking
// Records: souls_recorded, mission_role, hours_participated
// Tracks: contributed_to_debrief, financial_contribution
```

#### Background Processing Command
```bash
php artisan missions:generate-wrapped {school_term_id?}
```
Command to pre-calculate and cache engagement summaries for all members.

### Additional Enhancements
- Time-series data for trend visualization
- Peer rankings (sensitively presented)
- Custom date range filtering
- Export to PDF/image for sharing
- Push notifications for badge achievements
- Team/group engagement comparisons
- Automated summary generation at term/year end
- Real-time summary updates via observers

### Performance Optimizations
```sql
-- Recommended indexes for better query performance
ALTER TABLE mission_subscriptions ADD INDEX idx_member_status_date (member_id, status, created_at);
ALTER TABLE souls ADD INDEX idx_mission_decision (mission_id, decision_type);
ALTER TABLE course_members ADD INDEX idx_member_completion (member_id, completion_status, completed_at);
```

---

## Use Cases

### Mobile App "Wrapped" Feature
This endpoint can be used to generate an Instagram-story-like "wrapped" experience showing members their yearly engagement statistics with achievement badges and comparative insights.

### Personal Dashboard
Display member engagement metrics on a personal dashboard to encourage continued participation and highlight areas of growth.

### Ministry Analytics
Aggregate engagement data across members to identify highly engaged members, popular mission types, and areas for improvement.

---

## Notes
- All date/time values are in ISO 8601 format
- Streak calculations allow for reasonable gaps (e.g., 90 days for missions, 2 days for learning)
- Comparative statistics are calculated against all active members in the system
- Year filtering applies to the `created_at` timestamp of engagement records
- The key is to make every member feel valued and show them the tangible impact of their involvement in the ministry

---

## Questions?

For any questions about this implementation, refer to:
1. This Documentation: `docs/MEMBER_ENGAGEMENT_COMPLETE.md`
2. Test Cases: `tests/Unit/MemberEngagementTest.php`
3. Business Logic: `app/Jobs/MemberEngagement/GetEngagementJob.php`
4. Controller: `app/Http/Controllers/API/MemberEngagementController.php`
