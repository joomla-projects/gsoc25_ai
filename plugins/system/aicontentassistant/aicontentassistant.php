<?php

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class PlgSystemAicontentassistant extends CMSPlugin
{
    /**
     * Load plugin language file automatically so that it can be used inside component
     *
     * @var    boolean
     * @since  ___DEPLOY___VERSION___
     */
    protected $autoloadLanguage = true;
    protected $app;

    public function onBeforeCompileHead()
    {
        // Only load in administrator area
        if (!$this->app->isClient('administrator')) {
            return;
        }

        // Check if we are on an article edit page
        if ($this->isArticleEditPage()) {
            $this->addSimpleAIForm();
        }
    }

    private function isArticleEditPage()
    {
        $input = $this->app->input;
        $option = $input->get('option');
        $view = $input->get('view');
        $layout = $input->get('layout', 'default');

        return $option === 'com_content' && $view === 'article' && $layout === 'edit';
    }

    private function addSimpleAIForm()
    {        
        $html = '
        <style>
            :root {
                --jai-sidebar-width: clamp(360px, 30vw, 480px);
            }

            /* Smoothly adjust when padding changes */
            body { transition: padding-right 0.25s ease-in-out; }
            body.ai-sidebar-open { padding-right: var(--jai-sidebar-width); }

            /* AI Assistant Sidebar */
            #ai-assistant-sidebar { 
                position: fixed; 
                top: 0; 
                right: 0; 
                height: 100vh; 
                width: var(--jai-sidebar-width);
                background: #fff; 
                border-left: 3px solid #007cba; 
                box-shadow: -8px 0 25px rgba(0,0,0,0.25); 
                z-index: 9999; 
                transform: translateX(100%);  /* hidden by default */
                transition: transform 0.25s ease-in-out;
                display: flex; 
                flex-direction: column;
                font-family: Arial, sans-serif;
            }

            #ai-assistant-sidebar.ai-open { transform: translateX(0); }

            #ai-assistant-header {
                display: flex; 
                align-items: center; 
                justify-content: space-between; 
                padding: 16px 18px; 
                border-bottom: 1px solid #e5e5e5; 
                background: #f7fbff;
            }

            #ai-assistant-title { 
                margin: 0; 
                font-size: 20px; /* larger, closer to Joomla admin banners */
                line-height: 1.3;
                color: #007cba; 
                font-weight: 800; 
                letter-spacing: .2px;
            }

            .ai-header-actions { display: flex; gap: 8px; }
            .ai-btn { 
                border: 1px solid #cbd5e1; 
                background: #fff; 
                color: #334155; 
                padding: 6px 10px; 
                border-radius: 6px; 
                cursor: pointer; 
                font-size: 12px; 
            }
            .ai-btn.primary { background: #007cba; color: #fff; border-color: #007cba; }
            .ai-btn:disabled { opacity: .6; cursor: not-allowed; }

            #ai-assistant-body { padding: 12px 14px; overflow: auto; flex: 1; }
            #ai-prompt { width: 100%; min-height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 6px; resize: vertical; font-size: 14px; }
            #ai-loading { display:none; text-align:center; padding: 12px; color: #007cba; }
            #ai-result-area { display:none; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e5e5; }
            #ai-result { background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 6px; font-size: 14px; line-height: 1.5; max-height: 35vh; overflow-y: auto; }

            /* Toggle button visible when sidebar is closed */
            #ai-assistant-toggle { 
                position: fixed; 
                top: 50%; 
                right: 0; /* on right edge when closed */
                transform: translateY(-50%);
                background: #007cba; 
                color: #fff; 
                padding: 10px 12px; 
                border-top-left-radius: 8px; 
                border-bottom-left-radius: 8px; 
                cursor: pointer; 
                z-index: 9999; 
                font-weight: 700; 
                letter-spacing: .5px; 
                box-shadow: -4px 0 12px rgba(0,0,0,0.2);
            }

            /* When sidebar is open, park the toggle on the dividing line (left edge of sidebar) */
            body.ai-sidebar-open #ai-assistant-toggle {
                right: var(--jai-sidebar-width);
            }
        </style>

        <div id="ai-assistant-sidebar" aria-hidden="true">
            <div id="ai-assistant-header">
                <h4 id="ai-assistant-title">AI Content Assistant</h4>
            </div>
            <div id="ai-assistant-body">
                <!-- Prompt box
                <div style="margin-bottom: 10px;">
                    <label style="display:block; margin-bottom:6px; font-weight:600;">Your Prompt</label>
                    <textarea id="ai-prompt" placeholder="Enter your prompt here..."></textarea>
                </div>
                -->
                <!-- Introduction Generator -->
                <div style="margin: 14px 0;">
                    <div style="font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                        <span>Introduction Generator</span>
                    </div>
                    <div style="display:grid; gap:8px; grid-template-columns: 1fr;">
                        <input id="ai-intro-title" type="text" placeholder="Article title" style="padding:8px; border:1px solid #ddd; border-radius:6px;" />
                        <input id="ai-intro-audience" type="text" placeholder="Target audience (optional)" style="padding:8px; border:1px solid #ddd; border-radius:6px;" />
                    </div>
                    <div style="display:flex; gap:8px; align-items:center; margin-top: 8px;">
                        <button onclick="generateAIIntroduction()" id="intro-generate-btn" class="ai-btn primary">Generate Introduction</button>
                        <div id="ai-intro-loading" style="display:none;">Generating introduction...</div>
                    </div>
                    <div id="ai-result-intro-area" style="display:none; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e5e5;">
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Introduction</label>
                        <div id="ai-result-intro" style="background:#f8fafc; border:1px solid #e2e8f0; padding:12px; border-radius:6px; font-size:14px; line-height:1.5; max-height:35vh; overflow-y:auto;"></div>
                        <div style="margin-top:10px;">
                            <button onclick="copyToClipboard(\'ai-result-intro\')" class="ai-btn">Copy Introduction</button>
                        </div>
                    </div>
                </div>
                <!-- Meta Description Generator -->
                <div style="margin: 16px 0;">
                    <div style="font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                        <span>Meta Description Generator</span>
                    </div>
                    <div style="display:grid; gap:8px; grid-template-columns: 1fr;">
                        <input id="ai-meta-title" type="text" placeholder="Article title" style="padding:8px; border:1px solid #ddd; border-radius:6px;" />
                        <input id="ai-meta-keywords" type="text" placeholder="Primary keyword(s)" style="padding:8px; border:1px solid #ddd; border-radius:6px;" />
                        <textarea id="ai-meta-summary" placeholder="Optional brief summary" style="min-height:70px; padding:8px; border:1px solid #ddd; border-radius:6px; resize: vertical;"></textarea>
                    </div>
                    <div style="display:flex; gap:8px; align-items:center; margin-top: 8px;">
                        <button onclick="generateMetaDescription()" id="meta-generate-btn" class="ai-btn primary">Generate Meta Description</button>
                        <div id="ai-meta-loading" style="display:none;">Generating meta description...</div>
                    </div>
                    <div id="ai-result-meta-area" style="display:none; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e5e5;">
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Meta Description</label>
                        <div id="ai-result-meta" style="background:#f8fafc; border:1px solid #e2e8f0; padding:12px; border-radius:6px; font-size:14px; line-height:1.5; max-height:35vh; overflow-y:auto; white-space:pre-wrap;"></div>
                        <div style="margin-top:10px;">
                            <button onclick="copyToClipboard(\'ai-result-meta\')" class="ai-btn">Copy Meta Description</button>
                        </div>
                    </div>
                </div>
                <!-- Prompt-based generation commented out per request
                <div style="display:flex; gap:8px; align-items:center; margin-bottom: 8px;">
                    <button onclick="generateAIContent()" id="generate-btn" class="ai-btn primary">Generate Content</button>
                    <div id="ai-loading">Generating content...</div>
                </div>
                -->
            </div>
        </div>
        <div id="ai-assistant-toggle" role="button" aria-controls="ai-assistant-sidebar" aria-expanded="false" title="Open AI Assistant">AI</div>';

        $html .= '
        <script>
        (function(){
            var sidebar = document.getElementById("ai-assistant-sidebar");
            var toggle = document.getElementById("ai-assistant-toggle");

            function openSidebar(){
                if (!sidebar) return;
                sidebar.classList.add("ai-open");
                document.body.classList.add("ai-sidebar-open");
            }

            function closeSidebar(){
                if (!sidebar) return;
                sidebar.classList.remove("ai-open");
                document.body.classList.remove("ai-sidebar-open");
            }

            if (toggle) toggle.addEventListener("click", function(){
                if (!sidebar) return;
                if (sidebar.classList.contains("ai-open")) closeSidebar(); else openSidebar();
            });
        })();

        function generateAIContent() {
            var promptEl = document.getElementById("ai-prompt");
            var prompt = (promptEl ? promptEl.value : "").trim();
            if (!prompt) {
                alert("Please enter a prompt!");
                return;
            }

            // Show loading
            document.getElementById("ai-loading").style.display = "block";
            var introArea = document.getElementById("ai-result-intro-area");
            if (introArea) introArea.style.display = "none";
            document.getElementById("generate-btn").disabled = true;

            var ajaxUrl = "' . Uri::root() . 'index.php?option=com_ajax&group=system&plugin=aicontentassistant&format=json";

            var requestData = { action: "generate", prompt: prompt };

            fetch(ajaxUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(requestData)
            })
            .then(function(response){ return response.text(); })
            .then(function(text){
                var cleanText = text;
                var jsonStart = cleanText.indexOf("{");
                if (jsonStart > 0) cleanText = cleanText.substring(jsonStart);
                try {
                    var data = JSON.parse(cleanText);
                    document.getElementById("ai-loading").style.display = "none";
                    document.getElementById("generate-btn").disabled = false;

                    var content = "";
                    if (data.success && data.content) {
                        content = data.content;
                    } else if (data.data && data.data[0]) {
                        var innerData = JSON.parse(data.data[0]);
                        if (innerData.success && innerData.content) content = innerData.content;
                    }

                    if (content) {
                        document.getElementById("ai-result").innerHTML = content.replace(/\\n/g, "<br>");
                        document.getElementById("ai-result-area").style.display = "block";
                    } else {
                        alert("No content received from AI");
                    }
                } catch(e) {
                    document.getElementById("ai-loading").style.display = "none";
                    document.getElementById("generate-btn").disabled = false;
                    alert("JSON Parse Error.");
                    console.error(e);
                }
            })
            .catch(function(error){
                document.getElementById("ai-loading").style.display = "none";
                document.getElementById("generate-btn").disabled = false;
                alert("Network Error: " + error.message);
            });
        }

        function generateAIIntroduction() {
            var title = (document.getElementById("ai-intro-title").value || "").trim();
            var audience = (document.getElementById("ai-intro-audience").value || "").trim();

            if (!title) {
                alert("Please enter a Title.");
                return;
            }

            document.getElementById("ai-intro-loading").style.display = "block";
            var introArea = document.getElementById("ai-result-intro-area");
            if (introArea) introArea.style.display = "none";
            document.getElementById("intro-generate-btn").disabled = true;

            var ajaxUrl = ' . "'" . Uri::root() . "index.php?option=com_ajax&group=system&plugin=aicontentassistant&format=json" . "'" . ';

            var requestData = {
                action: "introduction",
                title: title,
                audience: audience
            };

            fetch(ajaxUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(requestData)
            })
            .then(function(response){ return response.text(); })
            .then(function(text){
                var cleanText = text;
                var jsonStart = cleanText.indexOf("{");
                if (jsonStart > 0) cleanText = cleanText.substring(jsonStart);
                try {
                    var data = JSON.parse(cleanText);
                    document.getElementById("ai-intro-loading").style.display = "none";
                    document.getElementById("intro-generate-btn").disabled = false;

                    var content = "";
                    if (data.success && data.content) {
                        content = data.content;
                    } else if (data.data && data.data[0]) {
                        var innerData = JSON.parse(data.data[0]);
                        if (innerData.success && innerData.content) content = innerData.content;
                    }

                    if (content) {
                        var el = document.getElementById("ai-result-intro");
                        if (el) el.innerHTML = content.replace(/\n/g, "<br>");
                        if (introArea) introArea.style.display = "block";
                    } else {
                        alert("No content received from AI");
                    }
                } catch(e) {
                    document.getElementById("ai-intro-loading").style.display = "none";
                    document.getElementById("intro-generate-btn").disabled = false;
                    alert("JSON Parse Error.");
                    console.error(e);
                }
            })
            .catch(function(error){
                document.getElementById("ai-intro-loading").style.display = "none";
                document.getElementById("intro-generate-btn").disabled = false;
                alert("Error: " + error.message);
            });
    }

        function generateMetaDescription() {
            var title = (document.getElementById("ai-meta-title").value || "").trim();
            var keywords = (document.getElementById("ai-meta-keywords").value || "").trim();
            var summary = (document.getElementById("ai-meta-summary").value || "").trim();

            if (!title) {
                alert("Please enter the Article Title.");
                return;
            }
            if (!keywords) {
                alert("Please enter the primary keyword(s).");
                return;
            }

            document.getElementById("ai-meta-loading").style.display = "block";
            var metaArea = document.getElementById("ai-result-meta-area");
            if (metaArea) metaArea.style.display = "none";
            document.getElementById("meta-generate-btn").disabled = true;

            var ajaxUrl = ' . "'" . Uri::root() . "index.php?option=com_ajax&group=system&plugin=aicontentassistant&format=json" . "'" . ';
            var requestData = { action: "meta_description", title: title, keywords: keywords, summary: summary };

            fetch(ajaxUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(requestData)
            })
            .then(function(response){ return response.text(); })
            .then(function(text){
                var cleanText = text;
                var jsonStart = cleanText.indexOf("{");
                if (jsonStart > 0) cleanText = cleanText.substring(jsonStart);
                try {
                    var data = JSON.parse(cleanText);
                    document.getElementById("ai-meta-loading").style.display = "none";
                    document.getElementById("meta-generate-btn").disabled = false;

                    var content = "";
                    if (data.success && data.content) {
                        content = data.content;
                    } else if (data.data && data.data[0]) {
                        var innerData = JSON.parse(data.data[0]);
                        if (innerData.success && innerData.content) content = innerData.content;
                    }

                    if (content) {
                        var el = document.getElementById("ai-result-meta");
                        if (el) el.innerText = content;
                        if (metaArea) metaArea.style.display = "block";
                    } else {
                        alert("No content received from AI");
                    }
                } catch(e) {
                    document.getElementById("ai-meta-loading").style.display = "none";
                    document.getElementById("meta-generate-btn").disabled = false;
                    alert("JSON Parse Error.");
                    console.error(e);
                }
            })
            .catch(function(error){
                document.getElementById("ai-meta-loading").style.display = "none";
                document.getElementById("meta-generate-btn").disabled = false;
                alert("Error: " + error.message);
            });
        }
        function copyToClipboard(elementId) {
            var target = document.getElementById(elementId);
            var content = target ? (target.innerText || target.textContent || "") : "";
            navigator.clipboard.writeText(content).then(function() {
                alert("Content copied to clipboard! You can paste it into the editor.");
            }).catch(function() {
                alert("Please manually copy the generated content.");
            });
        }
        </script>';

        // Output the HTML directly
        echo $html;
    }

    public function onAjaxAicontentassistant()
    {
        // Force JSON content type
        header('Content-Type: application/json');
        
        try {
            error_log('AI Plugin: AJAX handler called');
            
            // Get the JSON input
            $json = file_get_contents('php://input');
            error_log('AI Plugin: Raw input: ' . $json);
            
            $data = json_decode($json, true);
            error_log('AI Plugin: Decoded data: ' . print_r($data, true));
            
            if ($data['action'] === 'generate' && !empty($data['prompt'])) {
                $prompt = $data['prompt'];
                error_log('AI Plugin: Processing prompt: ' . $prompt);
                
                // SIMPLE: Just call the AI framework directly
                $generatedContent = $this->callAI($prompt);
                error_log('AI Plugin: Generated content length: ' . strlen($generatedContent));
                
                $response = json_encode([
                    'success' => true,
                    'content' => $generatedContent
                ]);
                
                error_log('AI Plugin: Returning response: ' . substr($response, 0, 200) . '...');
                return $response;
            } elseif ($data['action'] === 'introduction') {
                $title = isset($data['title']) ? trim($data['title']) : '';
                $audience = isset($data['audience']) ? trim($data['audience']) : '';

                if ($title === '') {
                    return json_encode([
                        'success' => false,
                        'message' => 'Title is required.'
                    ]);
                }

                $promptParts = [];
                $promptParts[] = 'Write a single-paragraph introduction (about 60-120 words) for an article. Start with a hook.';
                $promptParts[] = "Article title: '" . $title . "'.";
                if ($audience !== '') { $promptParts[] = 'Target audience: ' . $audience . '.'; }
                $promptParts[] = 'Keep it concise, conversational, and avoid headings or bullet points.';
                $prompt = implode(' ', $promptParts);

                $generatedContent = $this->callAI($prompt);

                return json_encode([
                    'success' => true,
                    'content' => $generatedContent
                ]);
            } elseif ($data['action'] === 'meta_description') {
                $title = isset($data['title']) ? trim($data['title']) : '';
                $keywords = isset($data['keywords']) ? trim($data['keywords']) : '';
                $summary = isset($data['summary']) ? trim($data['summary']) : '';

                if ($title === '' || $keywords === '') {
                    return json_encode([
                        'success' => false,
                        'message' => 'Title and primary keyword(s) are required.'
                    ]);
                }

                // Build SEO-focused prompt for meta description
                $prompt = "You are an SEO assistant. Write a single meta description for an article. "
                    . "Constraints: 150-160 characters, include the primary keyword near the beginning, accurately summarize the page, and add a subtle call-to-action. "
                    . "Avoid quotes, emojis, hashtags, and line breaks. Output only the description sentence.\n\n"
                    . "Title: " . $title . "\n"
                    . "Primary keyword(s): " . $keywords . "\n";

                if ($summary !== '') {
                    $prompt .= "Brief summary/context: " . $summary . "\n";
                }

                $generatedContent = $this->callAI($prompt);

                $generatedContent = trim(preg_replace('/\s+/', ' ', $generatedContent));
                $generatedContent = trim($generatedContent, "\"'“”‘’ ");

                return json_encode([
                    'success' => true,
                    'content' => $generatedContent
                ]);
            }
            
            error_log('AI Plugin: Invalid request - no action or prompt');
            return json_encode([
                'success' => false,
                'message' => 'No prompt provided'
            ]);
            
        } catch (Exception $e) {
            error_log('AI Plugin: Exception: ' . $e->getMessage());
            error_log('AI Plugin: Stack trace: ' . $e->getTraceAsString());
            
            return json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function callAI($prompt)
    {
        // Load the AI framework files
        $autoloadFile = JPATH_ROOT . '/libraries/gsoc25_ai_framework/vendor/autoload.php';
        
        if (!file_exists($autoloadFile)) {
            throw new Exception('AI Framework autoloader not found. Please run "composer install" in the framework directory.');
        }
        
        require_once $autoloadFile;
        
        // Get the selected provider from plugin settings
        $provider = $this->params->get('default_provider', 'openai');
        error_log('AI Plugin: Using provider: ' . $provider);
        
        // Get provider-specific configuration
        $config = $this->getProviderConfig($provider);
        error_log('AI Plugin: Provider config prepared for: ' . $provider);
        
        try {
            // Create AI instance with selected provider
            $ai = \Joomla\AI\AIFactory::getAI($provider, $config);
            $response = $ai->chat($prompt);
            
            return $response->getContent();
            
        } catch (Exception $e) {
            error_log('AI Plugin: Provider error (' . $provider . '): ' . $e->getMessage());
            throw new Exception('AI Provider Error (' . ucfirst($provider) . '): ' . $e->getMessage());
        }
    }

    private function getProviderConfig($provider)
    {
        switch ($provider) {
            case 'openai':
                $apiKey = $this->params->get('openai_api_key', '');
                if (empty($apiKey)) {
                    throw new Exception('OpenAI API key not configured. Please enter your API key in the plugin settings.');
                }
                return ['api_key' => $apiKey];
                
            case 'anthropic':
                $apiKey = $this->params->get('anthropic_api_key', '');
                if (empty($apiKey)) {
                    throw new Exception('Anthropic API key not configured. Please enter your API key in the plugin settings.');
                }
                return ['api_key' => $apiKey];
                
            case 'ollama':
                $baseUrl = $this->params->get('ollama_base_url', 'http://localhost:11434');
                if (empty($baseUrl)) {
                    throw new Exception('Ollama base URL not configured. Please enter the server URL in the plugin settings.');
                }
                return ['base_url' => $baseUrl];
                
            default:
                throw new Exception('Unsupported AI provider: ' . $provider . '. Please select OpenAI, Anthropic, or Ollama.');
        }
    }

}