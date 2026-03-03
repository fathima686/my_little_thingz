# Template System Documentation

## Overview

The Template System provides a comprehensive solution for creating, managing, and using design templates in the custom requests workflow. It consists of two main components:

1. **Template Gallery** - Browse, manage, and select templates
2. **Template Editor** - Create and edit designs using Fabric.js canvas

## Features

### Template Gallery
- ✅ Browse templates by category
- ✅ Search templates by name/description
- ✅ Filter by featured templates
- ✅ Template usage tracking
- ✅ Template duplication
- ✅ Template management (CRUD operations)
- ✅ Category management
- ✅ Responsive grid/list view

### Template Editor
- ✅ Fabric.js-based canvas editor
- ✅ Text editing with font properties
- ✅ Shape tools (rectangle, circle)
- ✅ Image upload and placement
- ✅ Object manipulation (move, resize, rotate)
- ✅ Alignment tools
- ✅ Undo/Redo functionality
- ✅ Zoom controls
- ✅ Properties panel
- ✅ Export to PNG
- ✅ Save/Load designs
- ✅ Template integration

## File Structure

```
backend/
├── api/admin/
│   ├── template-gallery.php      # Template gallery API
│   └── template-editor.php       # Template editor API
├── database/
│   └── template-gallery-schema.sql # Database schema
├── setup-template-system.php     # Setup script
└── test-template-system.html     # API test page

frontend/
├── src/components/admin/
│   ├── TemplateGallery.jsx       # Template gallery component
│   └── TemplateEditor.jsx        # Template editor component
├── src/styles/
│   └── template-editor.css       # Editor styles
└── test-template-editor.html     # Editor test page
```

## Database Schema

### Tables Created

1. **design_templates** - Stores template definitions
2. **template_categories** - Template categories configuration
3. **template_usage** - Usage tracking
4. **custom_request_designs** - Extended for template integration

### Key Fields

- `template_data` (JSON) - Complete template definition with elements
- `canvas_width/height` - Canvas dimensions
- `is_public/is_featured` - Visibility flags
- `usage_count` - Popularity tracking

## API Endpoints

### Template Gallery API (`/api/admin/template-gallery.php`)

#### GET Requests
- `GET /` - Get all templates with categories
- `GET /?action=templates` - Get templates with filtering
- `GET /?action=template&id=X` - Get specific template
- `GET /?action=categories` - Get categories only

#### POST Requests
- `POST /` with `action: create` - Create new template
- `POST /` with `action: use` - Use template (track usage)
- `POST /` with `action: duplicate` - Duplicate template

#### PUT/DELETE Requests
- `PUT /` - Update template
- `DELETE /?id=X` - Delete template

### Template Editor API (`/api/admin/template-editor.php`)

#### GET Requests
- `GET /?action=design&request_id=X` - Get design for request
- `GET /?action=export&design_id=X` - Export design

#### POST Requests
- `POST /` with `action: save` - Save design
- `POST /` with `action: save-as-template` - Save as template
- `POST /` with `action: complete` - Complete design

## Setup Instructions

### 1. Database Setup

Run the setup script to create tables and sample data:

```bash
# Navigate to backend directory
cd backend

# Run setup script in browser
http://localhost/your-project/backend/setup-template-system.php
```

### 2. Dependencies

Ensure you have the following dependencies:

**Frontend:**
- React 18+
- Fabric.js 5.3+
- Tailwind CSS 2.2+
- Lucide React icons

**Backend:**
- PHP 7.4+
- MySQL 5.7+
- PDO extension

### 3. Configuration

Update your database configuration in `backend/config/database.php`:

```php
class Database {
    private $host = "localhost";
    private $db_name = "your_database";
    private $username = "your_username";
    private $password = "your_password";
    // ...
}
```

## Usage Examples

### Using Template Gallery Component

```jsx
import TemplateGallery from './components/admin/TemplateGallery';

function AdminPanel() {
    const handleTemplateSelect = (template) => {
        console.log('Selected template:', template);
        // Open template editor with selected template
    };

    const handleCreateNew = () => {
        console.log('Create new template');
        // Open blank template editor
    };

    return (
        <TemplateGallery
            onSelectTemplate={handleTemplateSelect}
            onCreateNew={handleCreateNew}
            inline={true}
        />
    );
}
```

### Using Template Editor Component

```jsx
import TemplateEditor from './components/admin/TemplateEditor';

function DesignWorkflow() {
    const [editorOpen, setEditorOpen] = useState(false);
    const [selectedTemplate, setSelectedTemplate] = useState(null);

    const handleSave = (designId) => {
        console.log('Design saved:', designId);
    };

    const handleComplete = (requestId, finalImageUrl) => {
        console.log('Design completed:', requestId, finalImageUrl);
        setEditorOpen(false);
    };

    return (
        <TemplateEditor
            template={selectedTemplate}
            requestId={123}
            isOpen={editorOpen}
            onClose={() => setEditorOpen(false)}
            onSave={handleSave}
            onComplete={handleComplete}
            customerImages={[]}
            inline={false}
        />
    );
}
```

### API Usage Examples

#### Load Templates

```javascript
const response = await fetch('/api/admin/template-gallery.php');
const data = await response.json();

if (data.status === 'success') {
    console.log('Templates:', data.templates);
    console.log('Categories:', data.categories);
}
```

#### Create Template

```javascript
const templateData = {
    action: 'create',
    name: 'My Template',
    category: 'Birthday',
    canvas_width: 800,
    canvas_height: 600,
    template_data: {
        version: '1.0',
        background: { type: 'solid', color: '#ffffff' },
        elements: [
            {
                type: 'text',
                content: 'Happy Birthday!',
                x: 100,
                y: 100,
                fontSize: 32,
                fill: '#333333'
            }
        ]
    }
};

const response = await fetch('/api/admin/template-gallery.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Admin-User-Id': '1'
    },
    body: JSON.stringify(templateData)
});
```

#### Save Design

```javascript
const designData = {
    action: 'save',
    request_id: 123,
    design_data: {
        version: '1.0',
        canvas: { width: 800, height: 600 },
        elements: [/* fabric.js objects */]
    },
    preview_url: 'data:image/png;base64,...'
};

const response = await fetch('/api/admin/template-editor.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Admin-User-Id': '1'
    },
    body: JSON.stringify(designData)
});
```

## Template Data Format

Templates use a standardized JSON format:

```json
{
    "version": "1.0",
    "background": {
        "type": "solid|gradient",
        "color": "#ffffff",
        "colors": ["#color1", "#color2"],
        "direction": "horizontal|vertical|diagonal|radial"
    },
    "elements": [
        {
            "type": "text",
            "content": "Text content",
            "x": 100,
            "y": 100,
            "fontSize": 24,
            "fontFamily": "Arial",
            "fontWeight": "normal|bold",
            "fontStyle": "normal|italic",
            "fill": "#000000",
            "textAlign": "left|center|right",
            "placeholder": true
        },
        {
            "type": "shape",
            "shape": "rectangle|circle",
            "x": 200,
            "y": 200,
            "width": 100,
            "height": 100,
            "fill": "#cccccc",
            "stroke": "#000000",
            "strokeWidth": 1,
            "rx": 0,
            "ry": 0
        },
        {
            "type": "image",
            "x": 300,
            "y": 300,
            "width": 200,
            "height": 150,
            "placeholder": true,
            "label": "Photo"
        }
    ]
}
```

## Testing

### 1. API Testing

Open `backend/test-template-system.html` in your browser to test:
- Template gallery API
- Template editor API
- Template creation
- Template usage

### 2. Component Testing

Open `frontend/test-template-editor.html` to test:
- Template editor UI
- Canvas manipulation
- Tool functionality
- Export features

### 3. Integration Testing

1. Run the setup script
2. Create sample templates
3. Test template selection
4. Test design editing
5. Test save/export functionality

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL server is running
   - Verify database exists

2. **CORS Errors**
   - Check API headers for `Access-Control-Allow-Origin`
   - Ensure frontend and backend are on same domain/port

3. **Fabric.js Not Loading**
   - Verify Fabric.js CDN link
   - Check browser console for errors
   - Ensure canvas element exists

4. **Template Not Saving**
   - Check API response in browser dev tools
   - Verify JSON format is valid
   - Check database permissions

### Debug Mode

Enable debug mode by adding to API files:

```php
ini_set("display_errors", 1);
error_reporting(E_ALL);
```

## Future Enhancements

- [ ] Template versioning
- [ ] Collaborative editing
- [ ] Advanced shape tools
- [ ] Layer management
- [ ] Template marketplace
- [ ] Batch operations
- [ ] Template analytics
- [ ] Mobile responsive editor
- [ ] Real-time preview
- [ ] Template sharing

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review API responses in browser dev tools
3. Check database logs
4. Test with sample data

## License

This template system is part of the custom requests application and follows the same licensing terms.