# Member Engagement Tracking - Implementation Summary

## Overview
This implementation adds a comprehensive member engagement tracking API endpoint that provides statistics for a "missions wrapped" feature, similar to Spotify Wrapped. It tracks member engagement across missions, learning, prayers, events, and calculates achievement badges.

## What Was Implemented

### 1. API Endpoint
**Route:** `GET /api/v1/members/{member_ulid}/engagement`

This endpoint allows mobile apps and dashboards to retrieve detailed engagement statistics for any member.

### 2. Key Features
- ✅ **Mission Participation Stats**: Total missions, streaks, favorite types, schools reached, roles, completion rates
- ✅ **Impact Metrics**: Souls touched, decision types breakdown, most impactful mission
- ✅ **Learning Progress**: Courses completed, lessons mastered, learning streaks
- ✅ **Prayer Engagement**: Response counts, consistency tracking
- ✅ **Event Participation**: Attended and upcoming events
- ✅ **Achievement Badges**: 6 different badges based on engagement levels
- ✅ **Comparative Stats**: Member performance vs community averages
- ✅ **Year Filtering**: View engagement for specific years

### 3. Architecture Pattern
Follows the established repository patterns:
- **Controller** → Uses sync jobs (like `PaymentInstructionController`)
- **Form Request** → Validates query parameters
- **Resource** → Formats API response consistently
- **Job** → Encapsulates business logic for calculating statistics
- **Tests** → Comprehensive Pest tests covering all scenarios

## Files Created

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
docs/
└── MEMBER_ENGAGEMENT_API.md                        # API documentation
routes/api/
└── v1.php                                          # Route registration (modified)
```

## Usage Examples

### Basic Request
```bash
GET /api/v1/members/{ulid}/engagement
Authorization: Bearer {token}
```

### With Badges
```bash
GET /api/v1/members/{ulid}/engagement?include_badges=true
```

### Yearly Stats with All Features
```bash
GET /api/v1/members/{ulid}/engagement?year=2024&include_badges=true&include_comparative_stats=true
```

## Response Example
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
      "schools_reached": 8,
      "completion_rate": 80.00
    },
    "impact_stats": {
      "souls_touched": 156,
      "decision_types": [...]
    },
    "learning_stats": {
      "courses_completed": 3,
      "lessons_completed": 28,
      "learning_progress_percentage": 65.50
    },
    "prayer_stats": {
      "prayer_responses": 42,
      "prayer_consistency_days": 28
    },
    "event_stats": {
      "events_attended": 8
    }
  }
}
```

## Achievement Badges

The system includes 6 achievement badges:

| Badge | Criteria | Icon |
|-------|----------|------|
| Mission Veteran | 10+ approved missions | 🎖️ |
| Soul Winner | 50+ souls touched | 👑 |
| Learning Champion | 3+ courses completed | 📚 |
| Prayer Warrior | 30+ prayer responses | 🙏 |
| School Explorer | 5+ different schools visited | 🗺️ |
| Faithful Servant | 5+ mission streak | ⭐ |

## Statistics Calculated

### Mission Stats
- Total missions subscribed
- Approved missions count
- Mission streak (consecutive missions)
- Favorite mission type
- Unique schools reached
- Role distribution
- Completion rate percentage

### Impact Stats
- Total souls touched through missions
- Breakdown by decision type (Salvation, Rededication, etc.)
- Most impactful mission with details

### Learning Stats
- Courses completed vs enrolled
- Total lessons completed
- Average learning progress
- Learning streak (consecutive days)
- Favorite course

### Prayer Stats
- Total prayer responses
- Prayer consistency (unique days with responses)

### Event Stats
- Total events attended
- Upcoming events count

### Comparative Stats (Optional)
- Average missions per member (community)
- Average courses per member (community)
- Areas where member is above average

## Testing

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

## Technical Implementation Details

### Streak Calculation
- **Mission Streak**: Allows up to 90 days gap between missions
- **Learning Streak**: Allows up to 2 days gap between lesson completions

### Performance Considerations
- Uses eager loading to minimize database queries
- Calculates stats efficiently using query builder where possible
- Caches relationships on the member model

### Data Filtering
- Year filtering applies to `created_at` timestamps
- Only counts approved mission subscriptions for mission stats
- Only counts completed courses/lessons for learning stats

## Mobile App Integration

This endpoint is designed for mobile apps to create engaging "wrapped" experiences:

1. **End of Year Summary**: Show members their yearly achievements
2. **Progress Tracking**: Display ongoing engagement metrics
3. **Gamification**: Use badges to encourage participation
4. **Social Sharing**: Members can share their wrapped stats
5. **Goal Setting**: Compare against community averages

## Future Enhancements

Potential additions for future iterations:
- Time-series data for trend visualization
- Peer rankings (sensitively presented)
- Custom date range filtering
- Export to PDF/image for sharing
- Push notifications for badge achievements
- Team/group engagement comparisons

## Documentation

Full API documentation available in: `docs/MEMBER_ENGAGEMENT_API.md`

## Questions?

For any questions about this implementation, refer to:
1. API Documentation: `docs/MEMBER_ENGAGEMENT_API.md`
2. Test Cases: `tests/Unit/MemberEngagementTest.php`
3. Business Logic: `app/Jobs/MemberEngagement/GetEngagementJob.php`
