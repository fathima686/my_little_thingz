import React, { useState, useEffect } from 'react';
import { 
  LuSearch, LuPlus, LuEye, LuCopy, LuTrash2, 
  LuStar, LuImage, LuGrid3X3, LuList,
  LuHeart, LuGift, LuMail, LuFrame, LuFileImage
} from 'react-icons/lu';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const categoryIcons = {
  'Birthday': LuGift,
  'Wedding': LuHeart,
  'Invitation': LuMail,
  'Posters': LuFileImage,
  'Photo Frames': LuFrame
};

export default function TemplateGallery({ onSelectTemplate, onCreateNew, inline = false }) {
  const [templates, setTemplates] = useState([]);
  const [categories, setCategories] = useState([]);
  const [groupedTemplates, setGroupedTemplates] = useState({});
  const [selectedCategory, setSelectedCategory] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [showFeaturedOnly, setShowFeaturedOnly] = useState(false);
  const [loading, setLoading] = useState(true);
  const [viewMode, setViewMode] = useState('grid'); // grid or list

  useEffect(() => {
    loadTemplateGallery();
  }, [selectedCategory, searchQuery, showFeaturedOnly]);

  const loadTemplateGallery = async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      if (selectedCategory) params.append('category', selectedCategory);
      if (searchQuery) params.append('search', searchQuery);
      if (showFeaturedOnly) params.append('featured', '1');
      
      const res = await fetch(`${API_BASE}/admin/template-gallery.php?${params}`);
      const data = await res.json();
      
      if (data.status === 'success') {
        setTemplates(data.templates || []);
        setCategories(data.categories || []);
        setGroupedTemplates(data.grouped || {});
      }
    } catch (err) {
      console.error('Error loading templates:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleTemplateSelect = (template) => {
    if (onSelectTemplate) {
      onSelectTemplate(template);
    }
  };

  const handleCreateBlank = () => {
    if (onCreateNew) {
      onCreateNew();
    }
  };

  const duplicateTemplate = async (template) => {
    try {
      const res = await fetch(`${API_BASE}/admin/template-gallery.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'duplicate',
          template_id: template.id
        })
      });
      const data = await res.json();
      if (data.status === 'success') {
        loadTemplateGallery();
      }
    } catch (err) {
      console.error('Error duplicating template:', err);
    }
  };

  const deleteTemplate = async (templateId) => {
    if (!confirm('Are you sure you want to delete this template?')) return;
    
    try {
      const res = await fetch(`${API_BASE}/admin/template-gallery.php?id=${templateId}`, {
        method: 'DELETE'
      });
      const data = await res.json();
      if (data.status === 'success') {
        loadTemplateGallery();
      }
    } catch (err) {
      console.error('Error deleting template:', err);
    }
  };

  const TemplateCard = ({ template }) => {
    const IconComponent = categoryIcons[template.category] || LuImage;
    
    return (
      <div className="template-card bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
        <div className="template-preview relative">
          {template.preview_image_url ? (
            <img 
              src={template.preview_image_url} 
              alt={template.name}
              className="w-full h-48 object-cover rounded-t-lg"
            />
          ) : (
            <div className="w-full h-48 bg-gray-100 rounded-t-lg flex items-center justify-center">
              <div className="text-center text-gray-400">
                <IconComponent className="w-12 h-12 mx-auto mb-2" />
                <p className="text-sm">No Preview</p>
              </div>
            </div>
          )}
          
          {template.is_featured && (
            <div className="absolute top-2 right-2 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs flex items-center">
              <LuStar className="w-3 h-3 mr-1" />
              Featured
            </div>
          )}
          
          <div className="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-50 transition-all duration-200 rounded-t-lg flex items-center justify-center opacity-0 hover:opacity-100">
            <div className="flex space-x-2">
              <button
                onClick={() => handleTemplateSelect(template)}
                className="bg-blue-600 text-white px-3 py-2 rounded-lg text-sm flex items-center hover:bg-blue-700"
              >
                <LuEye className="w-4 h-4 mr-1" />
                Use
              </button>
              <button
                onClick={() => duplicateTemplate(template)}
                className="bg-gray-600 text-white px-3 py-2 rounded-lg text-sm flex items-center hover:bg-gray-700"
              >
                <LuCopy className="w-4 h-4 mr-1" />
                Copy
              </button>
            </div>
          </div>
        </div>
        
        <div className="p-4">
          <div className="flex items-start justify-between mb-2">
            <h3 className="font-semibold text-gray-900 text-sm line-clamp-2">{template.name}</h3>
            <div className="flex items-center space-x-1 ml-2">
              <button
                onClick={() => deleteTemplate(template.id)}
                className="text-gray-400 hover:text-red-500 p-1"
              >
                <LuTrash2 className="w-4 h-4" />
              </button>
            </div>
          </div>
          
          {template.description && (
            <p className="text-gray-600 text-xs mb-2 line-clamp-2">{template.description}</p>
          )}
          
          <div className="flex items-center justify-between text-xs text-gray-500">
            <span className="bg-gray-100 px-2 py-1 rounded">{template.category}</span>
            <span>{template.canvas_width}×{template.canvas_height}</span>
          </div>
          
          {template.usage_count > 0 && (
            <div className="mt-2 text-xs text-gray-500">
              Used {template.usage_count} times
            </div>
          )}
        </div>
      </div>
    );
  };

  const CreateBlankCard = () => (
    <div 
      onClick={handleCreateBlank}
      className="template-card bg-white rounded-lg shadow-sm border-2 border-dashed border-gray-300 hover:border-blue-400 hover:shadow-md transition-all cursor-pointer"
    >
      <div className="h-48 flex items-center justify-center">
        <div className="text-center text-gray-400 hover:text-blue-500 transition-colors">
          <LuPlus className="w-12 h-12 mx-auto mb-2" />
          <p className="font-medium">Create Blank</p>
          <p className="text-sm">Start from scratch</p>
        </div>
      </div>
      <div className="p-4">
        <h3 className="font-semibold text-gray-700">Blank Canvas</h3>
        <p className="text-gray-500 text-xs mt-1">Design your own template</p>
      </div>
    </div>
  );

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className={`template-gallery ${inline ? 'inline-gallery' : 'full-gallery'}`}>
      {/* Header */}
      <div className="gallery-header mb-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-2xl font-bold text-gray-900">Template Gallery</h2>
          <div className="flex items-center space-x-2">
            <button
              onClick={() => setViewMode(viewMode === 'grid' ? 'list' : 'grid')}
              className="p-2 text-gray-500 hover:text-gray-700 border border-gray-300 rounded-lg"
            >
              {viewMode === 'grid' ? <LuGrid3X3 className="w-4 h-4" /> : <LuList className="w-4 h-4" />}
            </button>
          </div>
        </div>

        {/* Search and Filters */}
        <div className="flex flex-col sm:flex-row gap-4 mb-4">
          <div className="flex-1 relative">
            <LuSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
            <input
              type="text"
              placeholder="Search templates..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          
          <div className="flex items-center space-x-2">
            <label className="flex items-center space-x-2 text-sm">
              <input
                type="checkbox"
                checked={showFeaturedOnly}
                onChange={(e) => setShowFeaturedOnly(e.target.checked)}
                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span>Featured only</span>
            </label>
          </div>
        </div>

        {/* Category Filter */}
        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => setSelectedCategory('')}
            className={`px-4 py-2 rounded-full text-sm font-medium transition-colors ${
              selectedCategory === '' 
                ? 'bg-blue-600 text-white' 
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            All Categories
          </button>
          {categories.map((category) => {
            const IconComponent = categoryIcons[category.name] || LuImage;
            return (
              <button
                key={category.id}
                onClick={() => setSelectedCategory(category.name)}
                className={`px-4 py-2 rounded-full text-sm font-medium transition-colors flex items-center ${
                  selectedCategory === category.name 
                    ? 'bg-blue-600 text-white' 
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                }`}
              >
                <IconComponent className="w-4 h-4 mr-1" />
                {category.display_name}
                {category.template_count > 0 && (
                  <span className="ml-1 text-xs opacity-75">({category.template_count})</span>
                )}
              </button>
            );
          })}
        </div>
      </div>

      {/* Templates Grid */}
      <div className={`templates-grid ${
        viewMode === 'grid' 
          ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6' 
          : 'space-y-4'
      }`}>
        {/* Create Blank Card */}
        <CreateBlankCard />
        
        {/* Template Cards */}
        {templates.map((template) => (
          <TemplateCard key={template.id} template={template} />
        ))}
      </div>

      {/* Empty State */}
      {templates.length === 0 && (
        <div className="text-center py-12">
          <LuImage className="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">No templates found</h3>
          <p className="text-gray-500 mb-4">
            {searchQuery || selectedCategory 
              ? 'Try adjusting your search or filter criteria'
              : 'Create your first template to get started'
            }
          </p>
          <button
            onClick={handleCreateBlank}
            className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors"
          >
            Create Template
          </button>
        </div>
      )}
    </div>
  );
}