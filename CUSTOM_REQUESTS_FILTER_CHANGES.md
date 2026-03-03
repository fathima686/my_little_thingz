# 🔧 Custom Requests Filter & Button Changes

## Changes Needed

### 1. Status Filter Dropdown
Remove these options:
- ❌ "All"
- ❌ "Completed"
- ❌ "Cancelled"

Keep only:
- ✅ "Pending"
- ✅ "In Progress"

### 2. Action Buttons
Hide action buttons for completed/cancelled requests:
- ❌ Don't show "Complete" button if status is "completed"
- ❌ Don't show "Cancel" button if status is "cancelled"
- ❌ Don't show "Upload" button if status is "completed" or "cancelled"

## Implementation

Since your admin dashboard is a React app at `http://localhost:5173/admin`, you need to update the React component.

### Status Filter Update

**Current HTML:**
```html
<select class="select">
    <option value="pending">Pending</option>
    <option value="in_progress">In Progress</option>
    <option value="completed">Completed</option>
    <option value="cancelled">Cancelled</option>
    <option value="all">All</option>
</select>
```

**Updated HTML:**
```html
<select class="select">
    <option value="pending">Pending</option>
    <option value="in_progress">In Progress</option>
</select>
```

### Action Buttons Logic

**Current (shows all buttons):**
```jsx
<td>
    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
        <button class="btn btn-soft tiny">Open Editor</button>
        <button class="btn btn-outline tiny">View Images</button>
        <button class="btn btn-soft tiny">Complete</button>
        <button class="btn btn-danger tiny">Cancel</button>
        <label class="btn btn-outline tiny">Upload</label>
    </div>
</td>
```

**Updated (conditional rendering):**
```jsx
<td>
    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
        {/* Always show these */}
        <button class="btn btn-soft tiny">Open Editor</button>
        <button class="btn btn-outline tiny">View Images</button>
        
        {/* Only show if NOT completed or cancelled */}
        {status !== 'completed' && status !== 'cancelled' && (
            <>
                <button class="btn btn-soft tiny">Complete</button>
                <button class="btn btn-danger tiny">Cancel</button>
                <label class="btn btn-outline tiny">Upload</label>
            </>
        )}
    </div>
</td>
```

## Where to Make Changes

### React Component Location
Your React admin dashboard is likely in one of these locations:
- `my_little_things/src/pages/Admin.jsx`
- `my_little_things/src/components/CustomRequests.jsx`
- `my_little_things/src/pages/admin/CustomRequests.jsx`

### Find the Component
Look for the component that renders the custom requests table. It should have:
1. A status filter dropdown
2. A table with custom requests
3. Action buttons in each row

### Update the Code

#### 1. Update Status Filter
```jsx
// Find this section
<select className="select" onChange={handleStatusChange}>
    <option value="pending">Pending</option>
    <option value="in_progress">In Progress</option>
    {/* Remove these lines: */}
    {/* <option value="completed">Completed</option> */}
    {/* <option value="cancelled">Cancelled</option> */}
    {/* <option value="all">All</option> */}
</select>
```

#### 2. Update Action Buttons
```jsx
// Find the action buttons section
<td>
    <div style={{display: 'flex', gap: '6px', flexWrap: 'wrap'}}>
        <button className="btn btn-soft tiny" onClick={() => openEditor(request.id)}>
            Open Editor
        </button>
        <button className="btn btn-outline tiny" onClick={() => viewImages(request.id)}>
            View Images
        </button>
        
        {/* Add conditional rendering */}
        {request.status !== 'completed' && request.status !== 'cancelled' && (
            <>
                <button className="btn btn-soft tiny" onClick={() => completeRequest(request.id)}>
                    Complete
                </button>
                <button className="btn btn-danger tiny" onClick={() => cancelRequest(request.id)}>
                    Cancel
                </button>
                <label className="btn btn-outline tiny">
                    Upload
                    <input type="file" accept="image/*" style={{display: 'none'}} />
                </label>
            </>
        )}
    </div>
</td>
```

## Benefits

### Cleaner Interface
- ✅ Only shows relevant status filters
- ✅ Removes clutter from dropdown
- ✅ Focuses on active requests

### Better UX
- ✅ Can't accidentally complete already completed requests
- ✅ Can't cancel already cancelled requests
- ✅ Clearer what actions are available

### Logical Flow
- ✅ Completed requests don't need action buttons
- ✅ Cancelled requests don't need action buttons
- ✅ Only active requests show full actions

## Default Filter

Set the default filter to "Pending" or "In Progress":

```jsx
const [statusFilter, setStatusFilter] = useState('pending'); // or 'in_progress'
```

This way, when the page loads, it shows pending requests by default.

## Testing

### Test Status Filter
1. Open admin dashboard: `http://localhost:5173/admin`
2. Check status dropdown
3. Should only see "Pending" and "In Progress"
4. Should NOT see "All", "Completed", or "Cancelled"

### Test Action Buttons
1. View a request with status "pending" or "in_progress"
2. Should see: Open Editor, View Images, Complete, Cancel, Upload
3. View a request with status "completed"
4. Should see: Open Editor, View Images only
5. Should NOT see: Complete, Cancel, Upload

## Summary

✅ Remove "All", "Completed", "Cancelled" from status filter
✅ Hide Complete/Cancel/Upload buttons for completed/cancelled requests
✅ Keep Open Editor and View Images always visible
✅ Cleaner, more logical interface
✅ Better user experience

## Note

Since I don't have access to your React source files in this workspace, you'll need to:
1. Find the React component that renders the custom requests table
2. Apply the changes shown above
3. Test the functionality
4. The design editor redirect will work correctly once you make these changes

The design editor is already configured to redirect to `http://localhost:5173/admin#custom-requests` ✅
