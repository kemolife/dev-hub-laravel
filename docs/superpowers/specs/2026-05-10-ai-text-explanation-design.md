# AI Text Explanation Feature — Design Spec

**Date:** 2026-05-10
**Status:** Approved

---

## Overview

Authenticated readers can select any text in a post and ask an AI (via Ollama) to explain it. The result streams into a quick modal. Users can continue chatting in a right-side panel or a dedicated chat page. Conversations are public by default (visible to other authenticated users as highlights on the post) but owners can mark them private.

---

## Data Model

### `ai_conversations`

| column | type | notes |
|---|---|---|
| id | uuid | PK |
| user_id | bigint FK | owner |
| post_id | bigint FK | context post |
| selected_text | text | text that started the conversation |
| selection_start | int | char offset in post body |
| selection_end | int | char offset in post body |
| is_private | boolean, default false | owner can mark private |
| created_at / updated_at | timestamps | |

### `ai_messages`

| column | type | notes |
|---|---|---|
| id | uuid | PK |
| conversation_id | uuid FK | |
| role | enum: `user\|assistant` | |
| content | text | question or AI reply |
| created_at | timestamp | |

**Rules:**
- Multiple conversations per post (different users, different selections)
- Public conversations visible to all authenticated users on that post
- Others cannot append to another user's conversation (read-only)
- "Continue chatting" starts a new private conversation owned by current user, pre-populated with same `selected_text` and offsets; user types their first message

**Known limitation:** Post body edits after a conversation is created can shift char offsets, causing highlights to misalign. Accepted for v1 (posts rarely edited after publish).

---

## API

All routes under `/api/v1/`, Sanctum auth required.

```
POST   /posts/{slug}/conversations       # start new conversation + stream first AI reply
GET    /posts/{slug}/conversations       # list conversation metadata only (public + own private)
GET    /conversations/{id}              # full conversation with all messages
POST   /conversations/{id}/messages     # send follow-up message + stream AI reply
PATCH  /conversations/{id}              # toggle is_private
```

**Streaming response format:**
- `Content-Type: text/event-stream`
- Server proxies Ollama token stream as SSE
- Full assistant message saved to `ai_messages` after stream completes

**Rate limiting:** `throttle:20,1` on AI endpoints (20 requests/minute per user).

---

## Backend Architecture

### OllamaClient (`app/Services/OllamaClient.php`)
Thin wrapper around Laravel `Http` facade. Reads `OLLAMA_BASE_URL` and `OLLAMA_MODEL` from env. Posts to Ollama `/api/chat` and returns a streamed response. Throws `OllamaUnavailableException` on connection failure.

Future path: can be wrapped as a Laravel MCP tool if needed — the service interface stays stable.

### Actions
- `StartConversationAction` — creates `ai_conversations` record + first `ai_messages` (user role), calls `OllamaClient`, streams reply, saves assistant message on completion
- `ContinueConversationAction` — appends user message, calls `OllamaClient`, streams reply, saves assistant message

### Form Requests
- `StartConversationRequest` — validates `selected_text` (required, non-blank), `selection_start`, `selection_end`, `post_id`
- `ContinueConversationRequest` — validates `content` (required, non-blank)

### Policy (`ConversationPolicy`)
- `view`: owner always; others only if `is_private = false`
- `update` (toggle privacy): owner only
- `addMessage`: owner only

---

## Frontend UX

### Text Selection → Tooltip
User selects text in post body → floating "Ask AI" button appears near selection. Frontend validates selection is non-empty before showing button.

### Quick Explanation Modal
- Shows selected text (quoted)
- Streams AI answer token-by-token via `EventSource` / `fetch` with streaming
- Two actions: "Close" or "Continue chatting →"
- After stream completes, conversation persisted; highlight marker placed on selected text

### Highlights on Post Content
- Public conversations render as subtle highlight on post body using stored char offsets
- Hover/click → tooltip shows conversation preview + "View full conversation"

### Right-Side Chat Panel
- Fixed position, independent of scroll, does not interrupt reading
- Easy close/hide button
- Header: selected text snippet
- Message list: user questions + streamed AI replies
- Input at bottom for follow-up messages
- "Open full chat" link to dedicated chat page

### Chat Page (`/conversations/{id}`)
- Full page for continued private chats
- Message list + input, no post content alongside
- Only conversation owner can see private conversations; others get 403

---

## Error Handling

| Scenario | Backend | Frontend |
|---|---|---|
| Ollama unavailable | 503 + message | Inline error in modal/panel |
| Stream interrupted | Partial message saved | "Response interrupted" notice |
| Rate limit exceeded | 429 | "Too many requests, try again shortly" |
| Empty selection | 422 from Form Request | Button not shown (frontend guard) |
| Private conversation accessed by other | 403 from Policy | Redirect or error message |

---

## Environment Variables

```
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=llama3.2
```

---

## Testing

### Feature Tests (PHPUnit)
- `StartConversationTest` — authenticated user starts conversation, message saved, stream response returns
- `ContinueConversationTest` — follow-up message appended, stream returns
- `ConversationVisibilityTest` — public visible to others, private returns 403
- `TogglePrivacyTest` — owner can mark private, non-owner cannot
- `OllamaUnavailableTest` — mock `OllamaClient` throwing exception → 503
- `RateLimitTest` — 21st request in 1 min → 429

### Unit Tests
- `OllamaClientTest` — mock Http facade, verify correct payload sent to Ollama
- `StartConversationActionTest` — verifies conversation + messages created correctly

### Manual (v1)
- Text selection → button appears
- Modal streams correctly
- Panel open/close/hide
- Highlight renders on public conversations
- Rate limit error shown cleanly

---

## Out of Scope (v1)
- Forking another user's conversation
- Admin moderation of public conversations
- Ollama model selection per user
- E2E automated tests
