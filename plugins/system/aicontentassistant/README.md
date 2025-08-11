# AI Content Assistant Plugin

A Joomla system plugin that integrates with the GSoC 2025 AI Framework to provide AI-powered content generation capabilities within Joomla's admin interface.

## Phase 1 Testing - Plugin Foundation

We've just completed Phase 1 with the following files:

### Files Created:
1. `aicontentassistant.xml` - Plugin manifest with configuration
2. `aicontentassistant.php` - Main plugin class
3. `language/en-GB/plg_system_aicontentassistant.ini` - Language strings
4. `language/en-GB/plg_system_aicontentassistant.sys.ini` - System language strings
5. `media/css/aicontentassistant.css` - Plugin styles
6. `media/js/aicontentassistant.js` - Plugin JavaScript

### What We Can Test Now:

#### 1. Plugin Installation
- Copy the entire `plugins/system/aicontentassistant` folder to your Joomla installation's `plugins/system/` directory
- Go to Joomla Admin → Extensions → Discover → Discover
- Find "System - AI Content Assistant" and install it

#### 2. Plugin Configuration
- Go to Extensions → Plugins
- Find "System - AI Content Assistant" 
- Click to open configuration
- **Expected:** Configuration form with API key fields, provider selection, feature toggles

#### 3. Plugin Activation & Basic Function
- Enable the plugin
- Go to Content → Articles → Add New Article (or edit existing)
- Open browser Developer Tools (F12) → Console tab
- **Expected Console Messages:**
  ```
  AI Content Assistant: Plugin loaded successfully!
  AI Content Assistant: Article edit page detected
  AI Content Assistant: Initializing...
  AI Content Assistant: Button added to editor toolbar (or TinyMCE toolbar)
  ```

#### 4. AI Assistant Button
- In the article editor, look for "🤖 AI Assistant" button
- **Location:** Either in TinyMCE toolbar or near the editor
- Click the button
- **Expected:** Alert popup saying "AI Content Assistant Modal will open here!"

#### 5. AJAX Test
- While on article edit page, open browser console
- Run this command:
  ```javascript
  fetch(window.AIContentAssistantConfig.ajaxUrl + '&action=test&' + window.AIContentAssistantConfig.token + '=1')
    .then(r => r.json())
    .then(console.log)
  ```
- **Expected Response:**
  ```json
  {
    "success": true,
    "message": "AI Content Assistant AJAX is working!",
    "data": {
      "timestamp": "2025-08-07 12:34:56",
      "action": "test",
      "config": {
        "provider": "openai",
        "hasOpenAIKey": false,
        "hasAnthropicKey": false,
        "ollamaUrl": "http://localhost:11434"
      }
    }
  }
  ```

### Success Criteria for Phase 1:
- ✅ Plugin installs without errors
- ✅ Configuration form appears and saves settings
- ✅ Plugin loads only on article edit pages
- ✅ AI Assistant button appears in editor
- ✅ Button click shows test alert
- ✅ AJAX communication works
- ✅ Configuration is passed to JavaScript

### Troubleshooting:

**Plugin not appearing in Discover:**
- Check file permissions
- Ensure all files are in correct structure
- Check Joomla error logs

**Button not appearing:**
- Check browser console for JavaScript errors
- Verify CSS/JS files are loading
- Try different editor (TinyMCE vs CodeMirror)

**AJAX not working:**
- Check if token is being passed correctly
- Verify plugin is enabled
- Check network tab for failed requests

## Next Phase: AI Framework Integration

Once Phase 1 is working, we'll add:
- AI Framework integration (loading our framework classes)
- Real content generation (article outlines, intros)
- Modal interface for better UX
- Content insertion into editor

## Configuration

### Required for AI Providers:
- **OpenAI**: Add your API key in plugin configuration
- **Anthropic**: Add your API key in plugin configuration  
- **Ollama**: Ensure Ollama is running on configured URL (default: http://localhost:11434)

### Features:
- Article Outline Generation
- Introduction Writing
- Meta Description Creation
- Image Generation (OpenAI only)

**Test this Phase 1 first, then we'll build Phase 2!**
