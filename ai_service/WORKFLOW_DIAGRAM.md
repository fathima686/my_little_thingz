# AI Image Generation - Visual Workflow

## User Journey

```
┌─────────────────────────────────────────────────────────────────────┐
│                         USER EXPERIENCE                              │
└─────────────────────────────────────────────────────────────────────┘

Step 1: Admin opens Template Editor
        │
        ▼
Step 2: Clicks "AI Image" button (✨ sparkle icon)
        │
        ▼
Step 3: Dialog opens with text input
        │
        ▼
Step 4: Types prompt: "a golden trophy"
        │
        ▼
Step 5: Clicks "Generate" button
        │
        ▼
Step 6: Loading indicator shows (30-90 seconds)
        │
        ▼
Step 7: Image appears on canvas
        │
        ▼
Step 8: User moves, resizes, rotates image
        │
        ▼
Step 9: Saves/exports design with AI image included
```

## Technical Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                      TECHNICAL ARCHITECTURE                          │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────────┐
│  React Frontend  │
│  (Port 3000)     │
└────────┬─────────┘
         │
         │ HTTP POST /generate-image
         │ { "prompt": "a golden trophy" }
         │
         ▼
┌──────────────────────────────────────────────────────────────────────┐
│  FastAPI Backend (Port 8001)                                         │
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ 1. VALIDATION                                                │   │
│  │    • Check prompt not empty                                  │   │
│  │    • Check length ≤ 500 chars                                │   │
│  │    • Sanitize input                                          │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                           │                                          │
│                           ▼                                          │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ 2. GEMINI PROMPT REFINEMENT                                  │   │
│  │    Input:  "a golden trophy"                                 │   │
│  │    Process: Send to Gemini API with system instructions      │   │
│  │    Output: "A professional golden trophy on a marble         │   │
│  │             pedestal, clean white background, no text,       │   │
│  │             no watermark, high quality, studio lighting,     │   │
│  │             printable design, professional photography"      │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                           │                                          │
│                           ▼                                          │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ 3. STABLE DIFFUSION GENERATION                               │   │
│  │    • Load model (cached after first run)                     │   │
│  │    • Apply refined prompt                                    │   │
│  │    • Apply negative prompt (avoid text, watermarks)          │   │
│  │    • Generate 512x512 image                                  │   │
│  │    • 30 inference steps                                      │   │
│  │    • Guidance scale: 7.5                                     │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                           │                                          │
│                           ▼                                          │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ 4. IMAGE STORAGE                                             │   │
│  │    • Generate timestamp filename                             │   │
│  │    • Save as PNG in generated_images/                        │   │
│  │    • Optimize file size                                      │   │
│  │    • Create accessible URL                                   │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                           │                                          │
│                           ▼                                          │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ 5. RESPONSE                                                  │   │
│  │    {                                                         │   │
│  │      "image_url": "http://localhost:8001/images/...",       │   │
│  │      "refined_prompt": "A professional golden trophy...",   │   │
│  │      "original_prompt": "a golden trophy"                   │   │
│  │    }                                                         │   │
│  └─────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────┘
         │
         │ JSON Response
         │
         ▼
┌──────────────────────────────────────────────────────────────────────┐
│  React Frontend                                                       │
│                                                                       │
│  1. Receive image URL                                                │
│  2. Load image from URL                                              │
│  3. Create fabric.Image object                                       │
│  4. Add to canvas at position (100, 100)                             │
│  5. Set as active object                                             │
│  6. User can now manipulate image                                    │
└──────────────────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                         DATA FLOW                                    │
└─────────────────────────────────────────────────────────────────────┘

User Input
    │
    │ "a golden trophy"
    │
    ▼
┌─────────────────────┐
│  Frontend State     │
│  prompt: string     │
└──────────┬──────────┘
           │
           │ HTTP POST
           │
           ▼
┌─────────────────────────────────────────┐
│  Backend Request                        │
│  {                                      │
│    "prompt": "a golden trophy"          │
│  }                                      │
└──────────┬──────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│  Gemini API                             │
│  Input: "a golden trophy"               │
│  Output: Enhanced prompt (200 words)    │
└──────────┬──────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│  Stable Diffusion                       │
│  Input: Enhanced prompt                 │
│  Output: Image tensor (512x512x3)       │
└──────────┬──────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│  File System                            │
│  Save: ai_generated_20260116_143022.png │
│  Location: generated_images/            │
└──────────┬──────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│  Backend Response                       │
│  {                                      │
│    "image_url": "http://...",           │
│    "refined_prompt": "...",             │
│    "original_prompt": "..."             │
│  }                                      │
└──────────┬──────────────────────────────┘
           │
           │ HTTP Response
           │
           ▼
┌─────────────────────────────────────────┐
│  Frontend State Update                  │
│  imageUrl: string                       │
│  refinedPrompt: string                  │
└──────────┬──────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│  Fabric.js Canvas                       │
│  fabric.Image object added              │
│  User can manipulate                    │
└─────────────────────────────────────────┘
```

## Component Interaction

```
┌─────────────────────────────────────────────────────────────────────┐
│                    COMPONENT ARCHITECTURE                            │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│  TemplateEditor.jsx                                              │
│                                                                   │
│  ┌────────────────────────────────────────────────────────┐     │
│  │  Toolbar                                                │     │
│  │  • Text Tool                                            │     │
│  │  • Shape Tool                                           │     │
│  │  • Image Upload Tool                                    │     │
│  │  • AI Image Tool ✨ ← NEW                              │     │
│  └────────────────────────────────────────────────────────┘     │
│                                                                   │
│  ┌────────────────────────────────────────────────────────┐     │
│  │  Canvas Area (Fabric.js)                                │     │
│  │  • Text objects                                         │     │
│  │  • Shape objects                                        │     │
│  │  • Uploaded images                                      │     │
│  │  • AI-generated images ← NEW                           │     │
│  └────────────────────────────────────────────────────────┘     │
│                                                                   │
│  ┌────────────────────────────────────────────────────────┐     │
│  │  AIImageGenerator Component ← NEW                       │     │
│  │  • Prompt input                                         │     │
│  │  • Generate button                                      │     │
│  │  • Loading indicator                                    │     │
│  │  • Result display                                       │     │
│  └────────────────────────────────────────────────────────┘     │
└──────────────────────────────────────────────────────────────────┘
                              │
                              │ API Call
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│  FastAPI Backend (main.py)                                       │
│                                                                   │
│  ┌────────────────────────────────────────────────────────┐     │
│  │  Endpoints                                              │     │
│  │  • GET  /                                               │     │
│  │  • GET  /health                                         │     │
│  │  • POST /generate-image ← MAIN                         │     │
│  │  • GET  /images/{filename}                              │     │
│  └────────────────────────────────────────────────────────┘     │
│                                                                   │
│  ┌────────────────────────────────────────────────────────┐     │
│  │  gemini_prompt.py                                       │     │
│  │  • refine_prompt_with_gemini()                          │     │
│  │  • create_fallback_prompt()                             │     │
│  └────────────────────────────────────────────────────────┘     │
│                                                                   │
│  ┌────────────────────────────────────────────────────────┐     │
│  │  diffusion_engine.py                                    │     │
│  │  • load_diffusion_model()                               │     │
│  │  • generate_image_with_diffusion()                      │     │
│  └────────────────────────────────────────────────────────┘     │
└──────────────────────────────────────────────────────────────────┘
```

## Error Handling Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                      ERROR HANDLING                                  │
└─────────────────────────────────────────────────────────────────────┘

User Input
    │
    ▼
┌─────────────────────┐
│  Validation         │
│  • Empty prompt?    │
│  • Too long?        │
└──────┬──────────────┘
       │
       ├─ Error → Show error message to user
       │
       ▼ Valid
┌─────────────────────┐
│  Gemini API Call    │
└──────┬──────────────┘
       │
       ├─ Error → Use fallback prompt refinement
       │
       ▼ Success
┌─────────────────────┐
│  Stable Diffusion   │
└──────┬──────────────┘
       │
       ├─ Out of Memory → Suggest reducing IMAGE_SIZE
       ├─ CUDA Error → Fall back to CPU
       ├─ Model Error → Return 500 error
       │
       ▼ Success
┌─────────────────────┐
│  Save Image         │
└──────┬──────────────┘
       │
       ├─ Disk Full → Return 500 error
       ├─ Permission → Return 500 error
       │
       ▼ Success
┌─────────────────────┐
│  Return Response    │
└─────────────────────┘
```

## Performance Timeline

```
┌─────────────────────────────────────────────────────────────────────┐
│                    PERFORMANCE TIMELINE                              │
└─────────────────────────────────────────────────────────────────────┘

First Generation (Cold Start):
0s ────────────────────────────────────────────────────────────── 300s
│                                                                      │
├─ 0-120s: Download Stable Diffusion model (~4GB)
├─ 120-180s: Load model into memory
├─ 180-182s: Gemini prompt refinement
├─ 182-270s: Image generation (30 steps)
├─ 270-271s: Save image
└─ 271s: Return response

Subsequent Generations (Warm):
0s ────────────────────────────────────────────────────────────── 90s
│                                                                     │
├─ 0-2s: Gemini prompt refinement
├─ 2-88s: Image generation (model cached)
├─ 88-89s: Save image
└─ 89s: Return response

With GPU (Warm):
0s ────────────────────────────────────────────────────────────── 15s
│                                                                     │
├─ 0-2s: Gemini prompt refinement
├─ 2-13s: Image generation (GPU accelerated)
├─ 13-14s: Save image
└─ 14s: Return response
```

## State Management

```
┌─────────────────────────────────────────────────────────────────────┐
│                    FRONTEND STATE                                    │
└─────────────────────────────────────────────────────────────────────┘

AIImageGenerator Component State:
┌────────────────────────────────────────┐
│ prompt: string                         │  User input
│ isGenerating: boolean                  │  Loading state
│ error: string | null                   │  Error message
│ refinedPrompt: string                  │  AI-enhanced prompt
│ generatedImageUrl: string              │  Result URL
└────────────────────────────────────────┘

TemplateEditor Component State:
┌────────────────────────────────────────┐
│ showAIGenerator: boolean               │  Dialog visibility
│ fabricCanvas: fabric.Canvas            │  Canvas instance
│ canvasHistory: string[]                │  Undo/redo
└────────────────────────────────────────┘
```

This visual workflow helps understand the complete system architecture and data flow!
