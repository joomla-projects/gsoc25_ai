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

            /* Inline status messages */
            .ai-status { font-size:12px; margin-top:6px; line-height:1.4; display:none; }
            .ai-status.ai-status-success { color:#047857; }
            .ai-status.ai-status-error { color:#b91c1c; }
            .ai-status.ai-status-info { color:#0f172a; }

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
                <div id="ai-generic-status" class="ai-status" style="display:none;"></div>
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
                    <div id="ai-intro-status" class="ai-status" style="display:none;"></div>
                    <div id="ai-result-intro-area" style="display:none; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e5e5;">
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Introduction</label>
                        <div id="ai-result-intro" style="background:#f8fafc; border:1px solid #e2e8f0; padding:12px; border-radius:6px; font-size:14px; line-height:1.5; max-height:35vh; overflow-y:auto;"></div>
                        <div style="margin-top:10px;">
                            <button onclick="copyToClipboard(\'ai-result-intro\')" class="ai-btn">Copy</button>
                            <button onclick="useIntroductionInEditor()" class="ai-btn primary" style="background:#0d9488; border-color:#0d9488;">Use Introduction</button>
                        </div>
                    </div>
                </div>
                <!-- Meta Description Generator -->
                <div style="margin: 14px 0;">
                    <div style="font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                        <span>Meta Description Generator</span>
                    </div>
                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                        <button onclick="generateMetaDescriptionAuto()" id="meta-generate-btn" class="ai-btn primary">Generate Meta Description</button>
                        <div id="ai-meta-loading" style="display:none;">Generating meta description...</div>
                    </div>
                    <div id="ai-meta-status" class="ai-status" style="display:none;"></div>
                    <div id="ai-result-meta-area" style="display:none; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e5e5;">
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Meta Description</label>
                        <div id="ai-result-meta" style="background:#f8fafc; border:1px solid #e2e8f0; padding:12px; border-radius:6px; font-size:14px; line-height:1.5; max-height:24vh; overflow-y:auto; white-space:pre-wrap;"></div>
                        <div style="margin-top:10px;">
                            <button onclick="copyToClipboard(\'ai-result-meta\')" class="ai-btn">Copy</button>
                            <button onclick="applyMetaDescriptionToField()" class="ai-btn primary" style="background:#0d9488; border-color:#0d9488;">Use Meta Description</button>
                        </div>
                    </div>
                </div>
                <!-- Image Generation -->
                <div style="margin: 14px 0;">
                    <div style="font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                        <span>Image Generator</span>
                    </div>
                    <div style="display:grid; gap:8px; grid-template-columns: 1fr;">
                        <input id="ai-image-prompt" type="text" placeholder="Describe the image you want" style="padding:8px; border:1px solid #ddd; border-radius:6px;" />
                    </div>
                    <div style="display:flex; gap:8px; align-items:center; margin-top: 8px;">
                        <button onclick="generateAIImage()" id="image-generate-btn" class="ai-btn primary">Generate Image</button>
                        <div id="ai-image-loading" style="display:none;">Generating image...</div>
                    </div>
                    <div id="ai-image-status" class="ai-status" style="display:none;"></div>
                    <div id="ai-result-image-area" style="display:none; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e5e5;">
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Generated Image</label>
                        <div id="ai-result-image-wrapper" style="background:#f8fafc; border:1px solid #e2e8f0; padding:12px; border-radius:6px; display:flex; flex-direction:column; gap:8px; align-items:flex-start;">
                            <img id="ai-result-image" alt="AI generated" style="max-width:100%; border-radius:4px; display:none;" />
                        </div>
                    </div>
                </div>
                <!-- Alt Text (Vision) Generator -->
                <div style="margin: 14px 0;">
                    <div style="font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                        <span>Alt Text Generator</span>
                    </div>
                    <div style="display:grid; gap:8px; grid-template-columns: 1fr;">
                        <input id="ai-alt-image-url" type="text" placeholder="Paste image URL" style="padding:8px; border:1px solid #ddd; border-radius:6px;" />
                    </div>
                    <div style="display:flex; gap:8px; align-items:center; margin-top:8px; flex-wrap:wrap;">
                        <button onclick="generateAltText()" id="alt-generate-btn" class="ai-btn primary">Generate Alt Text</button>
                        <div id="ai-alt-loading" style="display:none;">Analyzing image...</div>
                    </div>
                    <div id="ai-alt-status" class="ai-status" style="display:none;"></div>
                    <div id="ai-result-alt-area" style="display:none; margin-top:12px; padding-top:12px; border-top:1px solid #e5e5e5;">
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Generated Alt Text</label>
                        <div id="ai-result-alt" style="background:#f8fafc; border:1px solid #e2e8f0; padding:12px; border-radius:6px; font-size:14px; line-height:1.4; white-space:pre-wrap; word-break:break-word;"></div>
                        <div style="margin-top:10px;">
                            <button onclick="copyToClipboard(\'ai-result-alt\')" class="ai-btn">Copy</button>
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
                aiShowMessage("generic","Please enter a prompt.","error");
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
                        document.getElementById("ai-result").innerHTML = content.replace(/\n/g, "<br>");
                        document.getElementById("ai-result-area").style.display = "block";
                        aiShowMessage("generic","Content generated.","success");
                    } else {
                        aiShowMessage("generic","No content received from AI.","error");
                    }
                } catch(e) {
                    document.getElementById("ai-loading").style.display = "none";
                    document.getElementById("generate-btn").disabled = false;
                    aiShowMessage("generic","Response parse error.","error");
                    console.error(e);
                }
            })
            .catch(function(error){
                document.getElementById("ai-loading").style.display = "none";
                document.getElementById("generate-btn").disabled = false;
                aiShowMessage("generic","Request failed: " + error.message,"error");
            });
        }

        function generateAIIntroduction() {
            var title = (document.getElementById("ai-intro-title").value || "").trim();
            var audience = (document.getElementById("ai-intro-audience").value || "").trim();

            if (!title) { aiShowMessage("intro","Please enter a title.","error"); return; }

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
                        aiShowMessage("intro","Introduction generated.","success");
                    } else {
                        aiShowMessage("intro","No content received from AI.","error");
                    }
                } catch(e) {
                    document.getElementById("ai-intro-loading").style.display = "none";
                    document.getElementById("intro-generate-btn").disabled = false;
                    aiShowMessage("intro","Response parse error.","error");
                    console.error(e);
                }
            })
            .catch(function(error){
                document.getElementById("ai-intro-loading").style.display = "none";
                document.getElementById("intro-generate-btn").disabled = false;
                aiShowMessage("intro","Request failed: " + error.message,"error");
            });
        }

        function generateMetaDescriptionAuto() {
            var titleEl = document.querySelector("#jform_title");
            var title = titleEl ? (titleEl.value || "").trim() : "";
            if (!title) { aiShowMessage("meta","Enter an article title first.","error"); return; }

            var content = "";
            try { if (window.tinyMCE) { var ed = tinyMCE.get("jform_articletext"); if (ed) content = ed.getContent({format:"text"}); } } catch(e) {}
            if (!content) { var raw = document.querySelector("#jform_articletext"); if (raw) content = raw.value || raw.textContent || ""; }
            content = (content || "").replace(/\s+/g," ").trim();
            if (!content) { aiShowMessage("meta","Article content is empty. Add some content first.","error"); return; }
            var excerpt = content.substring(0, 2000);

            document.getElementById("ai-meta-loading").style.display = "block";
            var metaArea = document.getElementById("ai-result-meta-area");
            if (metaArea) metaArea.style.display = "none";
            document.getElementById("meta-generate-btn").disabled = true;

            var ajaxUrl = ' . "'" . Uri::root() . "index.php?option=com_ajax&group=system&plugin=aicontentassistant&format=json" . "'" . ';
            var requestData = { action: "meta_description_auto", title: title, content: excerpt };

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
                    if (data.success && data.content) { content = data.content; }
                    else if (data.data && data.data[0]) { var innerData = JSON.parse(data.data[0]); if (innerData.success && innerData.content) content = innerData.content; }
                    if (content) {
                        var el = document.getElementById("ai-result-meta"); if (el) { el.innerText = content; }
                        if (metaArea) metaArea.style.display="block";
                        aiShowMessage("meta","Meta description generated.","success");
                    } else { aiShowMessage("meta","No content received from AI.","error"); }
                } catch(e){
                    document.getElementById("ai-meta-loading").style.display = "none";
                    document.getElementById("meta-generate-btn").disabled = false;
                    aiShowMessage("meta","Response parse error.","error");
                    console.error(e);
                }
            })
            .catch(function(err){
                document.getElementById("ai-meta-loading").style.display = "none";
                document.getElementById("meta-generate-btn").disabled = false;
                aiShowMessage("meta","Request failed: " + err.message,"error");
            });
        }

        function useIntroductionInEditor() {
            var introEl = document.getElementById("ai-result-intro");
            if (!introEl) { aiShowMessage("intro","No introduction to use yet.","error"); return; }
            var text = introEl.innerText || "";
            if (!text.trim()) { aiShowMessage("intro","Generated introduction is empty.","error"); return; }
            var inserted = false;
            try {
                if (window.tinyMCE) { var ed = tinyMCE.get("jform_articletext"); if (ed) { ed.focus(); var safe = text.replace(/</g,"&lt;").replace(/>/g,"&gt;"); ed.selection.setContent("<p>" + safe + "</p>"); inserted = true; } }
            } catch(e) { console.warn("TinyMCE insert failed", e); }
            if (!inserted) {
                var ta = document.getElementById("jform_articletext"); if (ta) { ta.value += (ta.value ? "\n\n" : "") + text + "\n"; inserted = true; }
            }
            if (inserted) aiShowMessage("intro","Introduction inserted into editor.","success"); else aiShowMessage("intro","Could not insert introduction.","error");
        }

        function applyMetaDescriptionToField() {
            var metaEl = document.getElementById("ai-result-meta");
            if (!metaEl) { aiShowMessage("meta","No meta description yet.","error"); return; }
            var text = (metaEl.innerText || "").trim(); if (!text) { aiShowMessage("meta","Meta description is empty.","error"); return; }
            var field = document.getElementById("jform_metadesc"); if (field) { field.value = text; aiShowMessage("meta","Meta description applied to form field.","success"); }
            else { aiShowMessage("meta","Meta description field not found on this form.","error"); }
        }

        function generateAIImage() {
            var prompt = (document.getElementById("ai-image-prompt").value || "").trim();
            if (!prompt) { aiShowMessage("image","Please enter an image prompt.","error"); return; }
            document.getElementById("ai-image-loading").style.display = "block";
            document.getElementById("image-generate-btn").disabled = true;
            var area = document.getElementById("ai-result-image-area"); if (area) area.style.display = "none";
            var ajaxUrl = ' . "'" . Uri::root() . "index.php?option=com_ajax&group=system&plugin=aicontentassistant&format=json" . "'" . ';
            var requestData = { action: "generate_image", prompt: prompt };

            fetch(ajaxUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(requestData)
            })
            .then(function(response){ return response.text(); })
            .then(function(text){
                try { console.log("[AI Image][raw]", text.substring(0,500)); } catch(_e){}
                document.getElementById("ai-image-loading").style.display = "none";
                document.getElementById("image-generate-btn").disabled = false;
                var data = {}; try { data = JSON.parse(text); } catch(e) { aiShowMessage("image","Parse error.","error"); return; }
                // If this is a Joomla com_ajax wrapper, the actual payload is usually in data[0]
                if (data && Array.isArray(data.data) && data.data.length === 1 && typeof data.data[0] === "string") {
                    try {
                        var inner = JSON.parse(data.data[0]);
                        if (inner && (inner.success !== undefined || inner.image || inner.content)) {
                            console.log("[AI Image] Unwrapped com_ajax inner payload");
                            data = inner;
                        }
                    } catch(unErr) { console.warn("[AI Image] Failed to parse inner wrapper", unErr); }
                }
                if (data.debug && Array.isArray(data.debug)) { try { data.debug.forEach(d=>console.log("[AI Image][debug]",d)); } catch(_e){} }
                if (data.success && data.image) {
                    var base = data.image;
                    if (!base.startsWith("http") && !base.startsWith("data:")) {
                        base = "data:image/png;base64," + base;
                    }
                    var img = document.getElementById("ai-result-image");
                    img.src = base;
                    img.style.display = "block";
                    document.getElementById("ai-result-image-area").style.display = "block";
                    aiShowMessage("image","Image generated.","success");
                } else {
                    aiShowMessage("image", data.message || "Failed to generate image.","error");
                }
            })
            .catch(function(error){
                document.getElementById("ai-image-loading").style.display = "none";
                document.getElementById("image-generate-btn").disabled = false;
                aiShowMessage("image","Request failed: " + error.message,"error");
            });
        }

        function generateAltText() {
            var url = (document.getElementById("ai-alt-image-url").value || "").trim();
            if (!url) { aiShowMessage("alt","Enter an image URL first.","error"); return; }
            document.getElementById("ai-alt-loading").style.display="block";
            document.getElementById("alt-generate-btn").disabled=true;
            var area = document.getElementById("ai-result-alt-area"); if (area) area.style.display="none";
            var ajaxUrl = ' . "'" . Uri::root() . "index.php?option=com_ajax&group=system&plugin=aicontentassistant&format=json" . "'" . ';
            var requestData = { action: "generate_alt_text", image_url: url };
            fetch(ajaxUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(requestData)
            })
            .then(function(r){ return r.text(); })
            .then(function(txt){
                document.getElementById("ai-alt-loading").style.display="none";
                document.getElementById("alt-generate-btn").disabled=false;
                var data={}; try{ data=JSON.parse(txt);}catch(e){ aiShowMessage("alt","Parse error.","error"); return; }
                if (data && Array.isArray(data.data) && data.data.length===1 && typeof data.data[0]==="string") {
                    try { var inner = JSON.parse(data.data[0]); if (inner && inner.success !== undefined) data = inner; } catch(eParse){}
                }
                if (data.success && data.alt) {
                    var el = document.getElementById("ai-result-alt"); if (el) el.textContent = data.alt;
                    if (area) area.style.display="block";
                    aiShowMessage("alt","Alt text generated.","success");
                } else {
                    aiShowMessage("alt", data.message || "Failed to generate alt text.","error");
                }
            })
            .catch(function(err){
                document.getElementById("ai-alt-loading").style.display="none";
                document.getElementById("alt-generate-btn").disabled=false;
                aiShowMessage("alt","Request failed: "+err.message,"error");
            });
        }

        function copyToClipboard(elementId) {
            var target = document.getElementById(elementId);
            var content = target ? (target.innerText || target.textContent || "") : "";
            if (!content.trim()) {
                if (elementId === "ai-result-intro") aiShowMessage("intro","Nothing to copy.","error");
                else if (elementId === "ai-result-meta") aiShowMessage("meta","Nothing to copy.","error");
                else if (elementId === "ai-result-alt") aiShowMessage("alt","Nothing to copy.","error");
                else aiShowMessage("generic","Nothing to copy.","error");
                return;
            }
            navigator.clipboard.writeText(content).then(function() {
                if (elementId === "ai-result-intro") aiShowMessage("intro","Copied to clipboard.","success");
                else if (elementId === "ai-result-meta") aiShowMessage("meta","Copied to clipboard.","success");
                else if (elementId === "ai-result-alt") aiShowMessage("alt","Copied to clipboard.","success");
                else aiShowMessage("generic","Copied to clipboard.","success");
            }).catch(function() {
                if (elementId === "ai-result-intro") aiShowMessage("intro","Copy failed.","error");
                else if (elementId === "ai-result-meta") aiShowMessage("meta","Copy failed.","error");
                else if (elementId === "ai-result-alt") aiShowMessage("alt","Copy failed.","error");
                else aiShowMessage("generic","Copy failed.","error");
            });
        }

        // Inline status message helper
        function aiShowMessage(section, message, type) {
            var el = document.getElementById("ai-" + section + "-status");
            if (!el) return;
            if (!message) {
                el.textContent="";
                el.style.display="none";
                el.className="ai-status";
                return;
            }
            el.textContent = message;
            el.className = "ai-status ai-status-" + (type || "info");
            el.style.display = "block";
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
            } elseif ($data['action'] === 'meta_description_auto') {
                $title = isset($data['title']) ? trim($data['title']) : '';
                $content = isset($data['content']) ? trim($data['content']) : '';

                if ($title === '' || $content === '') {
                    return json_encode([
                        'success' => false,
                        'message' => 'Title and content are required.'
                    ]);
                }

                // Build prompt using article excerpt
                $excerpt = mb_substr($content, 0, 2000);
                $prompt = "You are an SEO assistant. Generate a single SEO meta description for the following article. "
                    . "Constraints: maximum 300 characters, compelling, accurate, includes a natural call-to-action, no quotes, no line breaks, no emojis. "
                    . "Return ONLY the description text.\n\nTitle: " . $title . "\nArticle excerpt: " . $excerpt;

                $generatedContent = $this->callAI($prompt);
                $generatedContent = trim(preg_replace('/\s+/', ' ', $generatedContent));
                $generatedContent = trim($generatedContent, "\"'“”‘’ ");

                // // Enforce character window
                // if (mb_strlen($generatedContent) > 300) {
                //     $generatedContent = mb_substr($generatedContent, 0, 300);
                //     $generatedContent = preg_replace('/[^\p{L}\p{N}\)]*$/u', '', $generatedContent);
                // }

                return json_encode([
                    'success' => true,
                    'content' => $generatedContent
                ]);
            } elseif ($data['action'] === 'generate_image' && !empty($data['prompt'])) {
                $prompt = trim($data['prompt']);
                try {
                    $base64 = $this->callAI($prompt, 'image');

                    // Ensure we have a string
                    if (!is_string($base64) || $base64 === '') {
                        error_log('AI Plugin: Image generation empty (non-string or empty)');
                        return json_encode([
                            'success' => false,
                            'message' => 'Empty image response from provider.'
                        ]);
                    }

                    // Strip prefix
                    if (str_starts_with($base64, 'data:')) {
                        $parts = explode(',', $base64, 2);
                        if (count($parts) === 2) {
                            $base64 = $parts[1];
                        }
                    }

                    // Basic base64 sanity check (length + charset)
                    if (!preg_match('/^[A-Za-z0-9+\/=]{100,}$/', $base64)) {
                        error_log('AI Plugin: Image base64 failed validation');
                        return json_encode([
                            'success' => false,
                            'message' => 'Image payload not recognized as base64.'
                        ]);
                    }

                    error_log('AI Plugin: Image response (base64) length: ' . strlen($base64));
                    return json_encode([
                        'success' => true,
                        'image' => $base64
                    ]);
                } catch (Exception $eImg) {
                    error_log('AI Plugin: Image generation error: ' . $eImg->getMessage());
                    return json_encode([
                        'success' => false,
                        'message' => $eImg->getMessage()
                    ]);
                }
            } elseif ($data['action'] === 'generate_alt_text' && !empty($data['image_url'])) {
                $imageUrl = trim($data['image_url']);
                if ($imageUrl === '') {
                    return json_encode(['success' => false, 'message' => 'Image URL required']);
                }
                // Prompt for alt text
                $prompt = 'You are an accessibility assistant. Your task is to generate concise, descriptive alt text for this image. Good alt text briefly describes key elements (people, objects, actions, setting, or text), avoids unnecessary detail, and skips quotes or phrases like- image of. Keep it under 125 characters and focus only on whats essential.';
                try {
                    $alt = $this->callAI($prompt, 'vision', ['image' => $imageUrl]);
                    $alt = trim(preg_replace('/\s+/', ' ', $alt));
                    if (strlen($alt) > 130) { $alt = substr($alt, 0, 130); }
                    $alt = trim($alt, "\"'“”‘’ ");
                    return json_encode(['success' => true, 'alt' => $alt]);
                } catch (Exception $ve) {
                    return json_encode(['success' => false, 'message' => $ve->getMessage()]);
                }
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

    private function callAI($prompt, $type = 'text', $options = [])
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

            if ($type === 'image') {
                $imgResponse = $ai->generateImage($prompt, ['response_format' => 'b64_json']);
                if (is_object($imgResponse) && method_exists($imgResponse, 'getContent')) {
                    return $imgResponse->getContent();
                }
                return (string) $imgResponse;
            }
            if ($type === 'vision') {
                $imageUrl = $options['image'] ?? '';
                if ($imageUrl === '') {
                    throw new Exception('Request missing image.');
                }

                $visionResponse = $ai->vision($prompt, $imageUrl, $options);
                if (is_object($visionResponse) && method_exists($visionResponse, 'getContent')) {
                    return $visionResponse->getContent();
                }
                return (string) $visionResponse;
            }

            // Default text mode
            $response = $ai->chat($prompt);
            return is_object($response) && method_exists($response, 'getContent')
                ? $response->getContent()
                : (string) $response;
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