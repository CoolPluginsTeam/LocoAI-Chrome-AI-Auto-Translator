const AutoTranslator = (function (window, $, gTranslateWidget) {
    // get Loco Translate global object.  
    const locoConf = window.locoConf;
    // get plugin configuration object.
    const configData = window.extradata;
    let translationPerformed = false;
    const { ajax_url: ajaxUrl, nonce: nonce, ATLT_URL: ATLT_URL, extra_class: rtlClass, api_key: apikey, dashboard_url: dashboardurl } = configData;
    const allStrings = locoConf.conf.podata;
    // Safely access nested properties without optional chaining
    let pluginOrThemeName = '';
    
    if (locoConf && locoConf.conf && locoConf.conf.project && locoConf.conf.project.bundle) {
        const isTheme = locoConf.conf.project.bundle.startsWith('theme.');
        
        if (isTheme) {
            pluginOrThemeName = locoConf.conf.project.domain || '';
        } else {
            const match = locoConf.conf.project.bundle.match(/^[^.]+\.(.*?)(?=\/)/);
            pluginOrThemeName = match ? match[1] : '';
        }
    }

    onLoad();
    function onLoad() {
        if (locoConf && locoConf.conf) {
            const { conf } = locoConf;
            // get all string from loco translate po data object
            allStrings.shift();
            const { locale, project } = conf;
            // create a project ID for later use in ajax request.
            const projectId = generateProjectId(project, locale);
            // create strings modal
            createStringsModal(projectId, 'yandex');
            createStringsModal(projectId, 'google');
            createStringsModal(projectId, 'deepl');
            createStringsModal(projectId, 'chatGPT');
            createStringsModal(projectId, 'geminiAI');
            createStringsModal(projectId, 'openAI');
            createStringsModal(projectId, 'ChromeAiTranslator');
            addStringsInModal(allStrings);
        }
    }

    function initialize() {

        const { conf } = locoConf;
        const { locale, project } = conf;
        const projectId = generateProjectId(project, locale);
        // Embbed Auto Translate button inside Loco Translate editor
        if ($("#loco-editor nav").find("#cool-auto-translate-btn").length === 0) {
            addAutoTranslationBtn();
        }

        //append auto translate settings model
        settingsModel();

        // on auto translate button click settings model
        $("#cool-auto-translate-btn").on("click", openSettingsModel);

        // open translation provider model 
        $("button.icon-robot[data-loco='auto']").on("click", openTranslationProviderModel);

        // open model with Yandex Translate Widget
        $("#atlt_yandex_translate_btn").on("click", function () {
            openYandexTranslateModel(locale);
        });
        // open Model with Google Translate Widget
        $("#atlt_google_translate_btn").on("click", function () {
            openGoogleTranslateModel(locale);
        });

        // on langauge change translate strings and run scrolling
        $("#google_translate_element").change(function () {
            gTranslateWidgetOnChange();
        });

        // open model with Chrome AI Translator
        $("#ChromeAiTranslator_settings_btn").on("click", function () {
            openChromeAiTranslatorModel(locale);
        });

        // open model with DeepL Translate Widget
        $("#atlt_chatGPT_btn").on("click", function () {
            const max_size = 70;
            const parts = [];
            let currentTab = 0;
            const source_String = {};

            const translatedObj = [];
            var plainStrArr = filterRawObject(allStrings, "plain");
            for (let i = 0; i < plainStrArr.length; i++) {
                source_String[i + 1] = plainStrArr[i].source;
            }
            openChatGPTTranslateModel(locale, projectId, max_size, parts, currentTab, translatedObj, source_String);
        });

        $("#atlt_openai_btn").on("click", function () {
            openTranslateModel("openAI", "OpenAI");
        });
        
        $("#atlt_geminiAI_btn").on("click", function () {
            openTranslateModel("geminiAI", "GeminiAI");
        });

        $("#atlt_deepl_btn").on("click", function () {
            openTranslateModel("deepl", "DeepL");
        });
        
        $(".atlt_addApikey_btn").on("click", function () {
            window.location.href = dashboardurl + '&tab=settings';
        });
        
        function openTranslateModel(modelIdPrefix, modelName) {
            const modelContainer = $(`div#${modelIdPrefix}-widget-model.${modelIdPrefix}-widget-container`);
            const defaultLangCode = locoConf.conf.locale.lang || null;
        
            const deeplLanguages = ['ar', 'bg', 'cs', 'da', 'de', 'el', 'en', 'en-gb', 'en-us', 'es', 'es-419', 'et', 'fi', 'fr', 'he', 'hu', 'id', 'it', 'ja', 'ko', 'lt', 'lv', 'nb', 'nl', 'pl', 'pt', 'pt-br', 'pt-pt', 'ro', 'ru', 'sk', 'sl', 'sv', 'th', 'tr', 'uk', 'vi', 'zh', 'zh-hans', 'zh-hant'];
            
            const supportedLanguages = modelIdPrefix === 'deepl' ? deeplLanguages : ['af', 'sq', 'am', 'ar', 'hy', 'az', 'eu', 'bs', 'bg', 'ca', 'ceb', 'cs', 'cy', 'da', 'nl', 'et', 'fi', 'gl', 'ka', 'de', 'el', 'gu', 'he', 'hi', 'hu', 'is', 'id', 'it', 'ja', 'jv', 'kn', 'kk', 'km', 'ko', 'lo', 'lv', 'lt', 'mk', 'ms', 'ml', 'mr', 'mn', 'ne', 'pl', 'ro', 'ru', 'sr', 'si', 'sk', 'sl', 'sw', 'sv', 'ta', 'te', 'th', 'tr', 'uk', 'ur', 'uz', 'vi', 'cy', 'ceb', 'be', 'bn', 'zh', 'zh-TW', 'en', 'tl', 'fr', 'fa', 'pt', 'pa', 'sd', 'es', 'bn_BD', 'fa_AF', 'fr_CA', 'fr_FR', 'es_ES', 'es_CL', 'es_VE', 'es_EC', 'es_DO', 'es_UY', 'es_PR', 'es_MX', 'es_GT', 'es_CO', 'es_CR', 'es_PE', 'es_AR', 'pt_BR', 'pt_PT', 'zh_TW', 'zh_CN', 'zh_HK', 'tl', 'zh', 'ja'];
        
            $("#atlt-dialog").dialog("close");
        
            if (!supportedLanguages.includes(defaultLangCode)) {
                modelContainer.find(".notice-container")
                    .addClass('notice inline notice-warning')
                    .html(`${modelName} Translator does not support this language.`);
        
                modelContainer.find(".atlt_string_container, .choose-lang, .atlt_save_strings, .translator-widget, .notice-info, .is-dismissible").hide();
            } else {
                modelContainer.find(".notice-container").removeClass().empty();
                modelContainer.find(".atlt_string_container, .choose-lang, .atlt_save_strings, .translator-widget").show();
            }
        
            modelContainer.fadeIn("slow");
        }
        
        const filterstring = filterRawObject(allStrings, "plain")
        const sourceValues = Object.fromEntries(
            filterstring.map((item, index) => [index + 1, item.source.trim().replace(/\s+/g, ' ')])
        );
        const selectedStringsBatches = calculateTokensInBatches(sourceValues);

        $("#geminiAI_translate_button").on("click", function () {
            ajaxCall(selectedStringsBatches, locale, sourceValues, 'geminiAI','google')
        });

        $("#openAI_translate_button").on("click", function () {
            ajaxCall(selectedStringsBatches, locale, sourceValues, 'openAI','openai')
        });

        $("#deepl_translate_button").on("click", function () {
            ajaxCall(selectedStringsBatches, locale, sourceValues, 'deepl','deepl')
        });

        // save string inside cache for later use
        $(".atlt_save_strings").on("click", onSaveClick);

    }

    function destoryGoogleYandexTranslator() {
        translationPerformed=false;
        $('.skiptranslate iframe[id=":1.container"]').contents().find('a[id=":1.close"][title="Close"] img').trigger("click");
        $('.yt-button__icon.yt-button__icon_type_right').trigger('click');
        $('.atlt_custom_model.google-widget-container,.atlt_custom_model.yandex-widget-container').find('.atlt_string_container').scrollTop(0);

        const progressContainer = $('.modal-body.google-widget-body,.modal-body.yandex-widget-body').find('.atlt_translate_progress');
        progressContainer.hide();
        progressContainer.find(".atlt_actions > .atlt_save_strings").prop("disabled", true);
        progressContainer.find(".atlt_stats").hide();
        progressContainer.find('.progress-wrapper').hide();
        progressContainer.find('#myProgressBar').css('width', '0');
        progressContainer.find('#progressText').text('0%');
    }

    function addStringsInModal(allStrings) {
        var plainStrArr = filterRawObject(allStrings, "plain");
        if (plainStrArr.length > 0) {
            printStringsInPopup(plainStrArr, type = "yandex");
            printStringsInPopup(plainStrArr, type = "google");
            printStringsInPopup(plainStrArr, type = "geminiAI");
            printStringsInPopup(plainStrArr, type = "openAI");
            printStringsInPopup(plainStrArr, type = "deepl");
            printStringsInPopup(plainStrArr, type = "ChromeAiTranslator");
        } else {
            $("#ytWidget").hide();
            $(".notice-container")
                .addClass('notice inline notice-warning')
                .html("There is no plain string available for translations.");
            $(".atlt_string_container, .choose-lang, .atlt_save_strings, #google_translate_element, .translator-widget,.chatGPT_save_close, .chatGPT_save_cont, .notice-info, .is-dismissible").hide();
        }
    }

    // create project id for later use inside ajax request.
    function generateProjectId(project, locale) {
        const { domain } = project || {};
        const { lang, region } = locale;
        return project ? `${domain}-${lang}-${region}` : `temp-${lang}-${region}`;
    }



    function calculateTokensInBatches(stringsObj) {
        const maxTokens = 500;
        let selectedStringsBatch = {};
        let totalTokensBatch = 0;
        let selectedStringsBatches = [];
        const entries = Object.entries(stringsObj);

        // Loop through each string to calculate tokens and organize them into batches
        for (let i = 0; i < entries.length; i++) {
            const [key, value] = entries[i];
            const length = value.length;
            const tokens = Math.ceil(length / 4);

            // Add the string to the current batch if it doesn't exceed the maximum tokens
            if (totalTokensBatch + tokens <= maxTokens) {
                selectedStringsBatch[key] = value;
                totalTokensBatch += tokens;
            } else {
                // If adding the string exceeds the maximum tokens, start a new batch
                selectedStringsBatches.push(selectedStringsBatch);
                selectedStringsBatch = { [key]: value };
                totalTokensBatch = tokens;
            }
        }

        // Add the last batch if it contains any strings
        if (Object.keys(selectedStringsBatch).length > 0) {
            selectedStringsBatches.push(selectedStringsBatch);
        }
        return selectedStringsBatches;
    }

    function ajaxCall(sourceValues, locale, allSourceValues, translator, selectedApi) {
        // Constants
        const BATCH_SIZE = 15;
        const DELAY = 0;

        // State management
        const state = {
            ajaxStore: [],
            totalSourceCount: Object.values(allSourceValues).reduce((sum, str) => sum + str.length, 0),
            isModalAppended: false,
            isTbodyEmpty: false,
            translatedResponse: [],
            totalTranslatedCount: 0,
            totalTranslatedWords: 0,
            currentIndex: 0,
            stopProcess: true,
            stopResponse: false,
            uiUpdated: false,
            startTime: new Date() // Add start time to state
        };

        // DOM elements
        const container = $(`#${translator}-widget-model`);
        const elements = {
            progressBar: container.find("#myProgressBar"),
            progressText: container.find("#progressText"),
            tbody: container.find(".atlt_strings_table > tbody.atlt_strings_body"),
            warningWrapper: container.find(".warning-massage-content"),
            warningMessage: container.find(".atlt_translate_warning-massage"),
            progressIndicator: container.find(".atlt_translate_progress"),
            translateButton: container.find(`#${translator}_translate_button`),
            stats: container.find('.atlt_stats')
        };

        // Initialize UI
        function initializeUI() {
            elements.progressIndicator.fadeIn("slow");
            container.find('.progress-wrapper').show();
            setupEventListeners();
        }

        // Setup event listeners
        function setupEventListeners() {
            container.find(`${translator}-widget-header .close`).on("click", () => {
                state.stopProcess = false;
            });

            container.find('.close-button').on("click", () => {
                elements.warningMessage.fadeOut("slow");
            });
        }

        // Process translated strings
        function processTranslatedStrings(translatedStrings, metadata, sourceValues, selectedApi) {
            const regex = /(?:\\{1,2}u([0-9a-fA-F]{4})|\\u([0-9a-fA-F]{4}))/g;
            const source = [];
            const target = [];

            const batchIndex = metadata && metadata.batchIndex ? metadata.batchIndex : 0;
            const requestIndex = metadata && metadata.requestIndex ? metadata.requestIndex : 0;
            const globalIndex = (batchIndex * BATCH_SIZE) + requestIndex;
            const originalSource = sourceValues[globalIndex];

            function decodeUnicode(str) {
                if (Array.isArray(str)) {
                    str = str.join('');
                }
                return str.replace(regex, (match, p1, p2) => String.fromCharCode(parseInt(p1 || p2, 16)));
            }

            function processEachString(data) {
                if (typeof data === 'object') {
                    const key = Object.keys(data)[0];
                    const value = data[key];
                    if (typeof value === 'string' && value.trim()) {
                        const cleanedTarget = decodeUnicode(value).replace(/\\/g, '');
                        if (originalSource && originalSource[key]) {
                            source.push(originalSource[key]);
                            target.push(cleanedTarget);
                        }
                    }
                }
            }

            if (Array.isArray(translatedStrings) || selectedApi === 'deepl') {
                if (originalSource && typeof originalSource === 'object') {
                    const orderedKeys = Object.keys(originalSource)
                        .map(k => parseInt(k, 10))
                        .sort((a, b) => a - b)
                        .map(n => String(n));

                    for (let i = 0; i < orderedKeys.length && i < translatedStrings.length; i++) {
                        const key = orderedKeys[i];
                        const val = translatedStrings[i];
                        if (typeof val === 'string' && val.trim()) {
                            const cleanedTarget = decodeUnicode(val).replace(/\\/g, '');
                            source.push(originalSource[key]);
                            target.push(cleanedTarget);
                        }
                    }
                }
            } else if (translatedStrings && typeof translatedStrings === 'object') {
                Object.keys(translatedStrings).forEach(key => {
                    if (Object.prototype.hasOwnProperty.call(translatedStrings, key)) {
                        processEachString({ [key]: translatedStrings[key] });
                    }
                });
            }

            return { source, target };
        }

        // Update progress UI
        function updateProgress() {
            const progressValue = Math.round((state.totalTranslatedCount / state.totalSourceCount) * 100);
            elements.progressBar.css('width', `${progressValue}%`);
            elements.progressText.text(`${progressValue}%`);
            elements.progressText.css('color','#f3f3f3');
        }

        // Handle successful translation
        function handleSuccessfulTranslation() {
            const message = state.totalTranslatedCount < state.totalSourceCount
                ? `Wahooo! You have saved your valuable time by using auto-translation. You have translated <strong class="totalChars">${state.totalTranslatedCount}</strong> characters Out of <strong class="totalChars">${state.totalSourceCount}</strong> characters using <strong><a href="https://wordpress.org/support/plugin/automatic-translator-addon-for-loco-translate/reviews/#new-post" target="_new">LocoAI – Auto Translate for Loco Translate (Pro)</a></strong>`
                : `Wahooo! You have saved your valuable time via auto translating <strong class="totalChars">${state.totalTranslatedCount}</strong> characters using <strong><a href="https://wordpress.org/support/plugin/automatic-translator-addon-for-loco-translate/reviews/#new-post" target="_new">LocoAI – Auto Translate for Loco Translate (Pro)</a></strong>`;

            elements.stats.html(message);
            elements.translateButton.prop('disabled', true).css({
                'background-color': '#cccccc',
                'cursor': 'not-allowed',
                'opacity': '1'
            });
        }

        // Make AJAX request
        function makeAjaxRequest(chunk, batchIndex, requestIndex) {
            const data = {
                action: 'atlt_geminiAI_openAI_ajax_handler',
                nonce: nonce,
                source_data: {
                    locale: locale,
                    source: chunk,
                    selectedApi: selectedApi
                },
                metadata: {
                    batchIndex: batchIndex,
                    requestIndex: requestIndex
                }
            };

            return new Promise((resolve, reject) => { 
                state.ajaxStore.push($.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (!state.stopResponse && !response.success) {
                            state.stopProcess = false;
                            state.stopResponse = true;
                            elements.warningWrapper.html(`<h2>${response.data}</h2>`);
                            elements.warningMessage.fadeIn("slow");
                            elements.progressIndicator.fadeOut("slow");
                            state.ajaxStore.forEach(item => item.abort());
                            resolve();
                            return;
                        }

                        if (response.success && response.data && response.data.data) {
                            const result = processTranslatedStrings(response.data.data , response.data.metadata, sourceValues ,selectedApi);
                            const { source, target } = result;
                            state.translatedResponse.push(Boolean(response.data.data));

                            let tbody = '';
                            for (let j = 0; j < source.length; j++) {
                                tbody += `<tr id="${state.currentIndex}"><td>${state.currentIndex + 1}</td><td class="notranslate source">${encodeHtmlEntity(source[j])}</td>`;
                                tbody += `<td class="target translate">${encodeHtmlEntity(target[j])}</td></tr>`;
                                state.currentIndex++;
                            }

                            state.totalTranslatedCount += source.reduce((sum, str) => sum + str.length, 0);
                            state.totalTranslatedWords += source.reduce((sum, str) => sum + str.trim().split(/\s+/).filter(word => word.length > 0).length, 0);
                            updateProgress();

                            if (!state.isModalAppended && tbody) {
                                elements.tbody.html('');
                                state.isModalAppended = true;
                            }

                            if (tbody) {
                                elements.tbody.append(tbody);
                                const stringContainer = container.find('.atlt_string_container');
                                stringContainer.off('scroll').stop();
                                
                                const tbodyScrollHeight = stringContainer.find('.atlt_strings_table tbody').prop('scrollHeight');
                                const scrollSpeed = 3000;

                                if (tbodyScrollHeight > 100) {
                                    if (container.css('display') === 'block') {
                                        stringContainer.animate({
                                            scrollTop: tbodyScrollHeight
                                        }, scrollSpeed, 'linear');
                                    }
                                }
                            } else {
                                handleEmptyResponse();
                            }
                        }
                        resolve();
                    },
                    error: reject
                }));
            });
        }

        // Handle empty response
        function handleEmptyResponse() {
            state.isTbodyEmpty = true;
            state.stopProcess = false;
            state.stopResponse = true;
            if (!elements.warningWrapper.find("h2:contains('Translation Aborted.')").length) {
                elements.warningWrapper.append("<h2>Translation Aborted.</h2>");
            }
            elements.warningMessage.fadeIn("slow");
            elements.progressIndicator.fadeOut("slow");
            state.ajaxStore.forEach(item => item.abort());
        }

        // Process chunks in batches
        async function processChunksInBatches() {
            try {
                for (let i = 0; i < sourceValues.length; i += BATCH_SIZE) {
                    if (container.css('display') === 'block' && !state.stopResponse) {
                        state.stopProcess = true;
                    }

                    if (state.stopProcess) {
                        const batch = sourceValues.slice(i, i + BATCH_SIZE);
                        const batchIndex = Math.floor(i / BATCH_SIZE);
                        
                        await Promise.allSettled(
                            batch.map((chunk, requestIndex) => 
                                makeAjaxRequest(chunk, batchIndex, requestIndex)
                            )
                        );
                        
                        if (i + BATCH_SIZE < sourceValues.length) {
                            await new Promise(resolve => setTimeout(resolve, DELAY));
                        }
                    } else {
                        break;
                    }
                }

                function updateTranslationUI() {
                    if (!state.uiUpdated) {
                        elements.progressBar.css({
                            'background-image': 'none',
                            'animation': 'none',
                            'background-size': 'none'
                        });
                        state.uiUpdated = true;
                        // Calculate time taken
                        const endTime = new Date();
                        const timeTaken = Math.round((endTime - state.startTime) / 1000); // Time in seconds
                        // Store time taken in container
                        container.data('translation-time', timeTaken);
                        container.data('translation-provider', translator);
                        // Format the character count as K/M
                        function formatNumberShort(n) {
                            n = Number(n);
                            if (n >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
                            if (n >= 1e3) return (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K';
                            return n.toString();
                        }
                        const formattedCount = formatNumberShort(state.totalTranslatedCount);
                        elements.progressIndicator.append(`Wahooo! You have saved your valuable time via auto translating ${formattedCount} characters using ${selectedApi === 'google'? 'GeminiAI': selectedApi === 'deepl'? 'DeepL': 'OpenAI'} Translator`);
                        setTimeout(() => {
                            container.find(".atlt_save_strings").prop("disabled", false);
                            elements.stats.fadeIn("slow");
                            elements.progressIndicator.fadeOut("slow");
                            handleSuccessfulTranslation();
                        }, 3000);
                    }
                }

                if (state.translatedResponse.some(Boolean) && !state.isTbodyEmpty) {
                    const stringContainer = container.find('.atlt_string_container');
                    const scrollHeight = stringContainer[0].scrollHeight;
                    const offsetHeight = stringContainer[0].offsetHeight;

                    stringContainer.on('scroll', function() {
                        const scrollHeight = stringContainer[0].scrollHeight;
                        const scrollTop = stringContainer[0].scrollTop;
                        const clientHeight = stringContainer[0].clientHeight;
                        const tolerance = 5;
                        const iscomplete = (Math.ceil(scrollTop + clientHeight) >= scrollHeight - tolerance);

                        if (iscomplete) {
                            updateTranslationUI();
                        }
                    });
                
                    if(offsetHeight == scrollHeight){
                        updateTranslationUI();
                    }
                } else {
                    elements.progressIndicator.fadeOut("slow");
                    handleEmptyResponse();
                }
            } catch (error) {
                console.error('An error occurred during the AJAX processing:', error);
                elements.progressIndicator.fadeOut("slow");
            }
        }

        // Initialize and start processing
        initializeUI();
        processChunksInBatches().catch(error => {
            console.error('An error occurred during the AJAX processing:', error);
            elements.progressIndicator.fadeOut("slow");
        });
    }


    // Yandex click handler
    function openYandexTranslateModel(locale) {
        const defaultcode = locale.lang || null;
        let defaultlang = '';

        const langMapping = {
            'bel': 'be',
            'snd': 'sd',
            'jv': 'jv',
            'nb': 'no',
            'nn': 'no'
            // Add more cases as needed
        };

        defaultlang = langMapping[defaultcode] || defaultcode;
        let modelContainer = $('div#yandex-widget-model.yandex-widget-container');

        modelContainer.find(".atlt_actions > .atlt_save_strings").prop("disabled", true);
        modelContainer.find(".atlt_stats").hide();
        localStorage.setItem("lang", defaultlang);

        const supportedLanguages = ['kir', 'he', 'af', 'jv', 'no', 'am', 'ar', 'az', 'ba', 'be', 'bg', 'bn', 'bs', 'ca', 'ceb', 'cs', 'cy', 'da', 'de', 'el', 'en', 'eo', 'es', 'et', 'eu', 'fa', 'fi', 'fr', 'ga', 'gd', 'gl', 'gu', 'he', 'hi', 'hr', 'ht', 'hu', 'hy', 'id', 'is', 'it', 'ja', 'jv', 'ka', 'kk', 'km', 'kn', 'ko', 'ky', 'la', 'lb', 'lo', 'lt', 'lv', 'mg', 'mhr', 'mi', 'mk', 'ml', 'mn', 'mr', 'mrj', 'ms', 'mt', 'my', 'ne', 'nl', 'no', 'pa', 'pap', 'pl', 'pt', 'ro', 'ru', 'si', 'sk', 'sl', 'sq', 'sr', 'su', 'sv', 'sw', 'ta', 'te', 'tg', 'th', 'tl', 'tr', 'tt', 'udm', 'uk', 'ur', 'uz', 'vi', 'xh', 'yi', 'zh'];

        if (!supportedLanguages.includes(defaultlang)) {
            $("#atlt-dialog").dialog("close");
            modelContainer.find(".notice-container")
                .addClass('notice inline notice-warning')
                .html("Yandex Automatic Translator Does not support this language.");
            modelContainer.find(".atlt_string_container, .choose-lang, .atlt_save_strings, #ytWidget, .translator-widget, .notice-info, .is-dismissible").hide();
            modelContainer.fadeIn("slow");
        } else {
            $("#atlt-dialog").dialog("close");
            // modelContainer.find('.notice, .inline, .notice-info, .is-dismissible').show();
            modelContainer.fadeIn("slow");
        }


    }

    function openGoogleTranslateModel(locale) {
        var defaultcode = locale.lang ? locale.lang : null;
        switch (defaultcode) {
            case 'bel':
                defaultlang = 'be';
                break;
            case 'he':
                defaultlang = 'iw';
                break;
            case 'snd':
                defaultlang = 'sd';
                break;
            case 'jv':
                defaultlang = 'jw';
                break;
            case 'nb':
                defaultlang = 'no';
                break;

            case 'nn':
                defaultlang = 'no';
                break;
            default:
                defaultlang = defaultcode;
                break;
        }
        var arr = ['ckb', 'szl', 'oci','kir', 'fur', 'bo', 'as', 'af', 'en', 'zh', 'no', 'sq', 'am', 'ar', 'hy', 'az', 'eu', 'be', 'bn', 'bs', 'bg', 'ca', 'ceb', 'ny', 'zh-CN', 'zh-TW', 'co', 'hr', 'cs', 'da', 'nl', 'eo', 'et', 'tl', 'fi', 'fr', 'fy', 'gl', 'ka', 'de', 'el', 'gu', 'ht', 'ha', 'haw', 'iw', 'hi', 'hmn', 'hu', 'is', 'ig', 'id', 'ga', 'it', 'ja', 'jw', 'kn', 'kk', 'km', 'rw', 'ko', 'ku', 'ky', 'lo', 'la', 'lv', 'lt', 'lb', 'mk', 'mg', 'ms', 'ml', 'mt', 'mi', 'mr', 'mn', 'my', 'ne', 'no', 'or', 'ps', 'fa', 'pl', 'pt', 'pa', 'ro', 'ru', 'sm', 'gd', 'sr', 'st', 'sn', 'sd', 'si', 'sk', 'sl', 'so', 'es', 'su', 'sw', 'sv', 'tg', 'ta', 'tt', 'te', 'th', 'tr', 'tk', 'uk', 'ur', 'ug', 'uz', 'vi', 'cy', 'xh', 'yi', 'yo', 'zu'];
        let modelContainer = $('div#google-widget-model.google-widget-container');
        modelContainer.find(".atlt_actions > .atlt_save_strings").prop("disabled", true);
        modelContainer.find(".atlt_stats").hide();
        if (arr.includes(defaultlang)) {
            $("#atlt-dialog").dialog("close");
            modelContainer.fadeIn("slow");
            // modelContainer.find('.notice, .inline, .notice-info, .is-dismissible').show();
            gTranslateWidget();
        } else {
            $("#atlt-dialog").dialog("close");
            modelContainer.find(".notice-container")
                .addClass('notice inline notice-warning')
                .html("Google Automatic Translator Does not support this language.");
            modelContainer.find(".atlt_string_container, .choose-lang, .atlt_save_strings, .translator-widget, .notice-info, .is-dismissible").hide();
            modelContainer.fadeIn("slow");
        }
    }

    async function openChromeAiTranslatorModel(locale) {
        var defaultcode = locale.lang ? locale.lang : null;
        switch (defaultcode) {
            case 'bel':
                defaultlang = 'be';
                break;
            case 'he':
                defaultlang = 'iw';
                break;
            case 'snd':
                defaultlang = 'sd';
                break;
            case 'jv':
                defaultlang = 'jw';
                break;
            case 'nb':
                defaultlang = 'no';
                break;

            case 'nn':
                defaultlang = 'no';
                break;
            default:
                defaultlang = defaultcode;
                break;
        }

        let modelContainer = $('div#ChromeAiTranslator-widget-model.ChromeAiTranslator-widget-container');
        modelContainer.find(".atlt_actions > .atlt_save_strings").prop("disabled", true);
        modelContainer.find(".atlt_stats").hide();

        $("#atlt-dialog").dialog("close");
        modelContainer.fadeIn("slow");
        // modelContainer.find('.notice, .inline, .notice-info, .is-dismissible').show();
        gTranslateWidget();

        if (translationPerformed) {
            $("#ChromeAiTranslator-widget-model").find(".atlt_save_strings").prop("disabled", false);
        }
    }


    function gTranslateWidgetOnChange() {
        var container = $("#google-widget-model");
        var stringContainer = container.find('.atlt_string_container');
        const startTime = new Date(); // Add timestamp when translation starts

        function formatNumberShort(n) {
            n = Number(n);
            if (n >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
            if (n >= 1e3) return (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K';
            return n.toString();
        }

        function showGoogleTranslationSuccess(container) {
            var charCount = $('.atlt_stats .totalChars').first().text().trim();
            var formattedCharCount = formatNumberShort(charCount);
            var message = `Wahooo! You have saved your valuable time via auto translating ${formattedCharCount} characters using Google Translator`;
            if (!container.data('message-added')) {
                container.data('message-added', true);
                container.find(".atlt_translate_progress").append(message);
            }
        }

        stringContainer.scrollTop(0);
        var scrollHeight = stringContainer[0].scrollHeight;
        var scrollSpeed = Math.min(10000, scrollHeight);

        if (scrollHeight !== undefined && scrollHeight > 100) {
            container.find(".progress-wrapper").show();
            container.find(".atlt_translate_progress").fadeIn("slow");
            const progressBar=container.find(".progress-wrapper .progress-bar");

            setTimeout(() => {
                container = $("#google-widget-model");
                if (container.css('display') === 'block') {
                    stringContainer.animate({
                        scrollTop: scrollHeight + 2000
                    }, scrollSpeed * 2, 'linear');
                } else {
                    container.find(".atlt_translate_progress").fadeOut();
                }
            }, 2000);

            stringContainer.on('scroll', function (e) {
                container = $("#google-widget-model");
                if (container.css('display') === 'none') {
                    container.find(".atlt_translate_progress").fadeOut("slow");
                    stringContainer.stop();
                    stringContainer.scrollTop(0);
                    return;
                }
                
                var scrollTop = e.target.scrollTop;
                var scrollHeight = e.target.scrollHeight;
                var clientHeight = e.target.clientHeight;
                var scrollPercentage = (scrollTop / (scrollHeight - clientHeight)) * 100;
                progressBar.css('width', scrollPercentage + '%');
                progressBar.find('#progressText').text((Math.round(scrollPercentage * 10) / 10).toFixed(1) + '%');
                
                var isScrolledToBottom = ($(this).scrollTop() + $(this).innerHeight() + 50 >= $(this)[0].scrollHeight);
                
                if (isScrolledToBottom) {
                    showGoogleTranslationSuccess(container);
                    setTimeout(()=>{
                        onCompleteTranslation(container, startTime); // Pass startTime to completion handler
                    },4000)
                }
            });

            if (stringContainer.innerHeight() + 10 >= scrollHeight) {
                showGoogleTranslationSuccess(container);
                setTimeout(() => {
                    onCompleteTranslation(container, startTime); // Pass startTime to completion handler
                }, 1500);
            }
        } else {
            showGoogleTranslationSuccess(container);
            setTimeout(() => {
                onCompleteTranslation(container, startTime); // Pass startTime to completion handler
            }, 2000);
        }
    }

    function onCompleteTranslation(container, startTime) {
        translationPerformed = true;
        const isTranslated = $(".goog-te-combo option:selected").val();
        if (translationPerformed && isTranslated) {
            const endTime = new Date();
            const timeTaken = Math.round((endTime - startTime) / 1000); // Time in seconds
            container.find(".atlt_save_strings").prop("disabled", false);
            container.find(".atlt_stats").fadeIn("slow");    
            container.find(".atlt_translate_progress").fadeOut("slow");
            container.find(".atlt_string_container").stop();
            $('body').css('top', '0');

            // Store timeTaken in a data attribute on the container
            container.data('translation-time', timeTaken);
            container.data('translation-provider', 'google'); 
        } else {
            $('.atlt_custom_model.google-widget-container').find('.atlt_string_container').scrollTop(0);
            translationPerformed = false;
        }
    }

    //ChatGPT click handler
    function openChatGPTTranslateModel(locale, projectId, max_size, parts, currentTab, translatedObj, source_String) {
        modelContainer = $("div#chatGPT-widget-model.chatGPT-widget-container");
        $("#atlt-dialog").dialog("close");
        $(".modal-footer.chatGPT-widget-footer .atlt_actions").addClass("chatGPT_disable");
        $(".chatGPT_save_cont").addClass("btn-disabled");
        $(".chatGPT_save_close").addClass("btn-disabled");
        modelContainer.fadeIn("slow");
        modelContainer.find(".atlt_string_container, .atlt_save_strings").hide();
        createParts(parts, source_String, max_size, modelContainer);
        modelContainer.find(".chatGptError, .chatGPT_table, .chatGPT_table_close, .clear-button, .preview-button").hide();
        // modelContainer.find(`#prevButton, .notice, .inline, .notice-info, .is-dismissible`).show();
        
        showTab(currentTab);
        $(".chatGPT_step-1").addClass("chatGPT_steps-border");
        $(".chatGPT_step-2, .chatGPT_step-3").removeClass("chatGPT_steps-border");
        updateChatGptButtons(parts, currentTab, modelContainer, projectId);
        var totalParts = parts.length;
        var currentPart = 0;
        var progressbar = $("#chatGPT_progressbar");
        if (totalParts == 1) {
            $(".chatGPT_progress").hide();
            $(`#chatGPT_progress-label${currentTab}`).hide();
            $(".preview-button").css({
                "top": "231px"
            });
        }
        progressbar.css("width", 0);
        var ratioText = `(${(currentPart + 1)} / ${totalParts} Parts)`;
        $(`#chatGPT_progress-label${currentTab}`).text(ratioText);
        $("#prevButton").on("click", function () {
            if (currentTab > 0) {
                var errorContainer = $(`#chatGptError${currentTab}`);
                currentTab--;
                updateChatGptButtons(parts, currentTab, modelContainer, projectId);
                showTab(currentTab);
                $(`#chatGPT_table_close${currentTab}`).hide();
                $(`#table${currentTab}`).hide();
                $(`#Part${currentTab}`).show();
                $(`#chatGPT_steps${currentTab}`).show();
                if (errorContainer) {
                    // Hide the error for the current tab
                    $(".chatGptError, .clear-button, .preview-button").hide();
                    $("textarea").val("");
                }
                if (currentPart == 0) {
                    return;
                }
                currentPart--;
                progressbar.css("width", Math.round(100 * currentPart / totalParts) + "%");
                var ratioText = `(${(currentPart + 1)} / ${totalParts} Parts)`;
                const savedDataJSON = localStorage.getItem(`${projectId}-part${currentTab + 1}`);
                if (savedDataJSON) {
                    const savedData = JSON.parse(savedDataJSON);
                    updateTextareaWithTransformedData(savedData, currentTab);
                }
                $(`#chatGPT_progress-label${currentTab}`).text(ratioText);
                $(".modal-footer.chatGPT-widget-footer .atlt_actions").removeClass("chatGPT_disable");
                $(".chatGPT_save_cont").removeClass("btn-disabled");
                $(".chatGPT_save_close").removeClass("btn-disabled");
                $('.chatGPT_step-1, .chatGPT_step-2, .chatGPT_step-3').removeClass('chatGPT_steps-border');
            }
        });

        //   save the translation in localstorage and go to next page
        $(".chatGPT_save_cont:first").on("click", function () {
            $(".modal-footer.chatGPT-widget-footer .atlt_actions").addClass("chatGPT_disable");
            $(`#prevButton`).show();
            $(".chatGPT_save_cont").addClass("btn-disabled");
            $(".chatGPT_save_close").addClass("btn-disabled");
            $(`#prevButton, .notice, .inline, .notice-info, .is-dismissible`).show();
            const savedDataJSON = localStorage.getItem(`${projectId}-part${currentTab + 1}`);
            const savedDataJSON2 = localStorage.getItem(`${projectId}-part${currentTab + 2}`);
            if (savedDataJSON||savedDataJSON2) {
                if (currentTab < parts.length - 1) {
                    var errorContainer = $(`#chatGptError${currentTab}`);
                    currentTab++;
                    updateChatGptButtons(parts, currentTab, modelContainer, projectId);
                    showTab(currentTab);
                    if (errorContainer) {
                        // Hide the error for the current tab
                        $(".chatGptError, .clear-button, .preview-button").hide();
                        $("textarea").val("");
                    }
                    //Update Progress Bar
                    if (currentPart >= totalParts) {
                        return;
                    }
                    currentPart++;
                    progressbar.css("width", Math.round(100 * currentPart / totalParts) + "%");
                    var ratioText = `(${(currentPart + 1)} / ${totalParts} Parts)`;
                    $(`#chatGPT_progress-label${currentTab}`).text(ratioText);
                    if (localStorage.getItem(`${projectId}-part${currentTab + 1}`)) {
                        $(".modal-footer.chatGPT-widget-footer .atlt_actions").removeClass("chatGPT_disable");
                        $(".chatGPT_save_cont").removeClass("btn-disabled");
                        $(".chatGPT_save_close").removeClass("btn-disabled");
                        $(".chatGPT_step-1, .chatGPT_step-2, .chatGPT_step-3").removeClass("chatGPT_steps-border");
                        const saveData = JSON.parse(savedDataJSON2);
                        updateTextareaWithTransformedData(saveData, currentTab);
                    } else {
                        $(".chatGPT_step-2, .chatGPT_step-3").removeClass("chatGPT_steps-border");
                        $(".chatGPT_step-1").addClass("chatGPT_steps-border");
                    }
                }

            }
        });

        //   save the translation
        $(".chatGPT_save_close").on("click", function () {
            const savedDataJSON = localStorage.getItem(`${projectId}-part${currentTab + 1}`);
            if (savedDataJSON) {
                const mergedData = mergeLocalStorageData(parts, translatedObj);
                const totalChars = mergedData.reduce((sum, item) => sum + item.source.length, 0);
                const totalWords = mergedData.reduce((sum, item) => sum + item.source.split(/\s+/).length, 0);
                const translationProvider = 'ChatGPT';
                const timeTaken = 0;

                const translationData = {
                    time_taken: timeTaken,
                    translation_provider: translationProvider,
                    character_count: totalChars,
                    string_count: totalWords,
                    pluginORthemeName: pluginOrThemeName,
                }
                
                saveTranslatedStrings(mergedData, projectId, translationData);

                $("#chatGPT-widget-model").fadeOut("slow", function () {
                    for (let j = 0; j < parts.length; j++) {
                        localStorage.removeItem(`${projectId}-part${j + 1}`);
                        if ($(".container").length > 0) {
                            // If it exists, remove it
                            $(".container").remove();
                        }
                    }
                });
                $("html").addClass("merge-translations");
                updateLocoModel();
            }
        });

        // if there is data in localstorage then table is created using stored data
        createTableFromLocalStorage(parts, projectId);
        actionsPerPart(parts, locale, projectId, translatedObj, modelContainer);
    }

    function createParts(parts, source_String, max_size, modelContainer) {
        parts.length = 0;
        const tabContainer = document.getElementById("tabContainer");
        const sourceKeys = Object.keys(source_String);
        const maxTokensPerPart = 500;

        let part = {};
        let currentPartTokens = 0;
        let currentPartStrings = 0;

        for (const key of sourceKeys) {
            const keyTokens = countTokens(source_String[key]);

            // Check if adding the current key will exceed the token limit
            if (currentPartTokens + keyTokens > maxTokensPerPart) {

                // If the token limit is exceeded, create a new part
                parts.push(part);
                const tabContent = createPartsContent(parts.length - 1, modelContainer);
                tabContainer.appendChild(tabContent);

                // Reset the current part and counters
                part = {};
                currentPartTokens = 0;
                currentPartStrings = 0;
            }

            // Check if adding the current key will exceed the string limit
            if (currentPartStrings >= max_size) {
                // If the string limit is exceeded, create a new part
                parts.push(part);
                const tabContent = createPartsContent(parts.length - 1, modelContainer);
                tabContainer.appendChild(tabContent);

                // Reset the current part and counters
                part = {};
                currentPartTokens = 0;
                currentPartStrings = 0;
            }

            // Add the key to the current part
            part[key] = source_String[key];
            currentPartTokens += keyTokens;
            currentPartStrings++;

            // If token limit is reached within the current part, create a new part
            if (currentPartTokens >= maxTokensPerPart) {
                parts.push(part);
                const tabContent = createPartsContent(parts.length - 1, modelContainer);
                tabContainer.appendChild(tabContent);

                // Reset the current part and counters
                part = {};
                currentPartTokens = 0;
                currentPartStrings = 0;
            }
            $('.chatGptError').hide();
        }

        // Add any remaining keys to the last part
        if (Object.keys(part).length > 0) {
            parts.push(part);
            const tabContent = createPartsContent(parts.length - 1, modelContainer);
            tabContainer.appendChild(tabContent);
        }
        return tabContainer;
    }

    function countTokens(inputString) {
        return inputString.split(' ').length;
    }

    function createPartsContent(partIndex, modelContainer) {
        const containerDiv = document.createElement("div");
        containerDiv.className = "container";
        containerDiv.innerHTML = `
        <input type="hidden" data-nextIndexVal="${partIndex}" id= "input${partIndex}">
        <h1 style ="text-align: center;" id="Part${partIndex}">Part ${partIndex + 1} <span style= "font-size: large;" id="chatGPT_progress-label${partIndex}"></span></h1>
        <div class="formBody">
        <input type="hidden" id="translate${partIndex}">
    
        <table class="chatGPT_steps" id="chatGPT_steps${partIndex}">
            <tr>
            <td class= "chatGPT_step-1">
              <h2>Step 1</h2>
              <p>Click on the copy button to copy the strings</p>
              <button class="button button-primary copy-button" id="copyButton${partIndex}" type="button" name="copy">Copy</button>
            </td>
            <td class= "chatGPT_step-2">
              <h2>Step 2</h2>
              <p>Visit <a class = "chatGPT_step-2-anchor" href="https://chat.openai.com/" target="_blank">https://chat.openai.com/</a><br/>and paste strings in ChatGPT<br/>for translation.</p>
            </td>
            </tr>
            <tr>
            <td colspan="2" class= "chatGPT_step-3">
              <h2>Step 3</h2>
              <p>Now copy the translated string that ChatGPT gives you and paste it into the below section <span style="font-size: smaller;">(must be in JSON Format) <button type="button" class="preview-button button button-primary" id="preview${partIndex}">Preview</button></span></p>
              <div style="position: relative;">
                        <textarea id="output${partIndex}" class="output-box" rows="5" cols="130" style="width: 100%;" placeholder="Add translated strings here..."></textarea>
                        <button type="button" class="clear-button button button-primary" id="clearButton${partIndex}">Clear</button>
                </div>
            </td>
            </tr>
            </table>
            </div>
            <button type= "button" class="button button-primary chatGPT_table_close" id = "chatGPT_table_close${partIndex}" style="float:right; font-size:14px; margin: 10px 0;">Close</button>
            <div id="table${partIndex}" class = "chatGPT_table"></div>`;
        return containerDiv;
    }

    function showTab(tabIndex) {
        // Hide all tabs and show the selected tab
        $(".container").hide();
        $(`.container:eq(${tabIndex})`).show();
    }

    function errorHandling(errorMsg, currentTab) {
        $(".chatGptError").attr("id", `chatGptError${currentTab}`);
        $(`#chatGptError${currentTab}`).html(errorMsg);
    }

    function updateChatGptButtons(parts, currentTab, modelContainer, projectId) {
        if (parts.length <= 1) {
            $("#prevButton").prop("disabled", true);
            $(".chatGPT_save_cont").prop("disabled", true);
        } else if (currentTab === 0) {
            $("#prevButton").prop("disabled", true);
            $(".chatGPT_save_cont").prop("disabled", false);
        } else if (currentTab === parts.length - 1) {
            $("#prevButton").prop("disabled", false);
            $(".chatGPT_save_cont").prop("disabled", true);
        } else {
            $("#prevButton").prop("disabled", false);
            $(".chatGPT_save_cont").prop("disabled", false);
        }
    }

    function updateTextareaWithTransformedData(savedData, tabIndex) { 
        const textarea = document.getElementById(`output${tabIndex}`);
        if (textarea) {
            // Transform the data into sorted array format
            const transformedData = Object.entries(savedData).map(([key, value]) => ({
                id: parseInt(key),
                target: value.target
            }));
            // Sort the transformedData by id
            transformedData.sort((a, b) => a.id - b.id);
            // Update the textarea with the transformed data
            textarea.value = JSON.stringify(transformedData, null, 2);
        }
        $(`#clearButton${tabIndex}`).show();
        $(`#preview${tabIndex}`).show();
    }

    function createTableFromLocalStorage(parts, projectId) {
        parts.forEach((part, i) => {
            const savedDataJSON = localStorage.getItem(`${projectId}-part${i + 1}`);
            if (savedDataJSON) {
                const savedData = JSON.parse(savedDataJSON);
                // Create a table to display saved data
                let savedDataHTML = `<table class="table">`;
                savedDataHTML +=
                    '<tr><th scope="col">S. No.</th><th scope="col">Source</th><th scope="col">Target</th></tr>';

                // Iterate over the saved data and add rows to the table
                Object.keys(savedData).forEach((key, index) => {
                    const source = savedData[key].source;
                    const target = savedData[key].target;
                    const escapeSource = source ? escapeHtml(source) : ''; // Use an empty string if source is missing
                    const escapetarget = target ? escapeHtml(target) : ''; // Use an empty string if target is missing
                    savedDataHTML += `<tr><td>${key}</td><td class = "source">${escapeSource}</td><td class = "target">${escapetarget}</td></tr>`;
                });

                savedDataHTML += "</table>";

                // Append the table to the corresponding container
                const tableDiv = document.querySelector(`#table${i}`);
                tableDiv.innerHTML = savedDataHTML;
                $(".chatGPT_step-1").removeClass("chatGPT_steps-border");
                updateTextareaWithTransformedData(savedData, i);
                $(".modal-footer.chatGPT-widget-footer .atlt_actions").removeClass("chatGPT_disable");
                $(".chatGPT_save_cont").removeClass("btn-disabled");
                $(".chatGPT_save_close").removeClass("btn-disabled");
            } else {
                const tableDiv = document.querySelector(`#table${i}`);
                let tableHTML = `<table class="table">`;
                tableHTML +=
                    '<tr><th scope="col">S. No.</th><th scope="col">Source</th><th scope="col">Target</th></tr>';

                Object.keys(part).forEach((key, index) => {
                    const value = part[key];
                    const escapeValue = value ? encodeHtmlEntity(value) : '';
                    tableHTML += `<tr><td>${key}</td><td class = "source">${escapeValue}</td><td class = "target"></td></tr>`;
                });

                tableHTML += "</table>";
                tableDiv.innerHTML = tableHTML;
            }


        });
    }
    function escapeHtml(html) {
        return html.replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }

    function createPartsTable(i, textareaValue, projectId, jsonArray) {
        const dataToSave = {};
        // Validate textarea field
        function isValidJSONString(str) {
            try {
                JSON.parse(str);
                return true;
            } catch (error) {
                if (textareaValue.startsWith("You are a helpful assistant that translates and replies with well-formed JSON")) {
                    var errorMsg =
                        "Untranslated Strings! *You have pasted the untranslated strings, Please translate them first";
                    $(".chatGptError").show();
                    errorHandling(errorMsg, i);
                    return false;
                } else {
                    var errorMsg =
                        `Invalid JSON Format! *Please enter a valid JSON-formatted string in the textarea. Click <a href="https://locoaddon.com/docs/pro-plugin/ai-translation-issues-and-solutions/" target="_blank">here</a> to validate your JSON`;
                    $(".chatGptError").show();
                    errorHandling(errorMsg, i);
                }

            }
        }
        if (isValidJSONString(textareaValue)) {
            const parsedJSON = JSON.parse(textareaValue);
            if (Array.isArray(parsedJSON)) {
                const tableDiv = document.querySelector(`#table${i}`);
                if (parsedJSON && parsedJSON[0] && parsedJSON[0][1] && parsedJSON[0][1].source) {
                    // console.log(parsedJSON[0]);
                    if (firstSourceInTable === parsedJSON[0][1].source) {
                        tableDiv.innerHTML = "";
                        let resultHtml = `<table class="table">`;
                        resultHtml +=
                            '<tr><th scope="col">S. No.</th><th scope="col">Source</th><th scope="col">Target</th></tr>';
                        parsedJSON.forEach((obj, index) => {
                            if (typeof obj === "object" && obj !== null) {
                                const key = Object.keys(obj)[0];
                                const { source, target } = obj[key];
                                const escapeSource = source ? escapeHtml(source) : ''; // Use an empty string if source is missing
                                const escapetarget = target ? escapeHtml(target) : ''; // Use an empty string if target is missing

                                // Create a table for each array element
                                resultHtml += `<tbody><tr><td>${key}</td><td class="source">${escapeSource}</td><td class="target">${escapetarget}</td></tr></tbody>`;

                                // Store the data in the dataToSave object (you can modify this as needed)
                                dataToSave[key] = {
                                    source,
                                    target,
                                };
                                var errorMsg = "";
                                $(".chatGptError").hide();
                                errorHandling(errorMsg, i);
                                $(".modal-footer.chatGPT-widget-footer .atlt_actions").removeClass(
                                    "chatGPT_disable"
                                );
                                $(".chatGPT_save_cont").removeClass("btn-disabled");
                                $(".chatGPT_save_close").removeClass("btn-disabled");
                            }
                        });
                        resultHtml += "</table>";

                        // Append the table to the corresponding container
                        tableDiv.innerHTML += resultHtml;
                        // Save data in local storage (you can modify this as needed)
                        const dataToSaveJSON = JSON.stringify(dataToSave);
                        localStorage.setItem(`${projectId}-part${i + 1}`, dataToSaveJSON);

                        $(".chatGPT_step-1, .chatGPT_step-2, .chatGPT_step-3").removeClass(
                            "chatGPT_steps-border"
                        );
                        var errorMsg = "";
                        $(".chatGptError").hide();
                        errorHandling(errorMsg, i);
                        $(".modal-footer.chatGPT-widget-footer .atlt_actions").removeClass(
                            "chatGPT_disable"
                        );
                        $(".chatGPT_save_cont").removeClass("btn-disabled");
                        $(".chatGPT_save_close").removeClass("btn-disabled");
                    } else {
                        var errorMsg =
                            "Wrong translated strings! *Please enter right strings translation";
                        $(".chatGptError").show();
                        errorHandling(errorMsg, i);
                        return false;
                    }
                }
                else if (Array.isArray(parsedJSON) && parsedJSON.length > 0 && parsedJSON.every(item => item.hasOwnProperty('target'))) {
                    // console.log(parsedJSON[0]);
                    const matchingTranslations = [];
                    
                    // Find matching sources by ID
                    parsedJSON.forEach((item) => {
                        if (item.id) {
                            const sourceMatch = jsonArray.find(source => source.id === item.id);
                            if (sourceMatch) {
                                matchingTranslations.push({
                                    key: item.id,
                                    source: sourceMatch.source,
                                    target: item.target
                                });
                            }
                        }
                    });
    
    
                    if (matchingTranslations.length > 0) {
                        tableDiv.innerHTML = "";
                        let resultHtml = `<table class="table">`;
                        resultHtml += '<tr><th scope="col">S. No.</th><th scope="col">Source</th><th scope="col">Target</th></tr>';
                        
                        matchingTranslations.forEach((translation) => {
                            const escapeSource = translation.source ? escapeHtml(translation.source) : '';
                            const escapetarget = translation.target ? escapeHtml(translation.target) : '';
    
    
                            resultHtml += `<tbody><tr><td>${translation.key}</td><td class="source">${escapeSource}</td><td class="target">${escapetarget}</td></tr></tbody>`;

                                // Add data to save object
                               dataToSave[translation.key] = {
                                        source: translation.source,
                                    target: translation.target
                                };
                                var errorMsg = "";
                                $(".chatGptError").hide();
                                errorHandling(errorMsg, i);
                                $(".modal-footer.chatGPT-widget-footer .atlt_actions").removeClass(
                                    "chatGPT_disable"
                                );
                                $(".chatGPT_save_cont").removeClass("btn-disabled");
                                $(".chatGPT_save_close").removeClass("btn-disabled");
    
                        });
                        resultHtml += "</table>";

                        // Append the table to the corresponding container
                        tableDiv.innerHTML += resultHtml;
                        // Save data in local storage (you can modify this as needed)
                        const dataToSaveJSON = JSON.stringify(dataToSave);
                        localStorage.setItem(`${projectId}-part${i + 1}`, dataToSaveJSON);

                        $(".chatGPT_step-1, .chatGPT_step-2, .chatGPT_step-3").removeClass(
                            "chatGPT_steps-border"
                        );
                        var errorMsg = "";
                        $(".chatGptError").hide();
                        errorHandling(errorMsg, i);
                        $(".modal-footer.chatGPT-widget-footer .atlt_actions").removeClass(
                            "chatGPT_disable"
                        );
                        $(".chatGPT_save_cont").removeClass("btn-disabled");
                        $(".chatGPT_save_close").removeClass("btn-disabled");
                    } else {
                        var errorMsg =
                            "Wrong translated strings! *Please enter right strings translation";
                        $(".chatGptError").show();
                        errorHandling(errorMsg, i);
                        return false;
                    }
                }

            }
            // Ensure the parsedJSON is an object
            else if (typeof parsedJSON === "object" && !Array.isArray(parsedJSON)) {

                const keys = Object.keys(parsedJSON);
                const tableDiv = document.querySelector(`#table${i}`);
                const tableSourceCells = tableDiv.querySelectorAll(".source"); // Get all source cells in the table
                const firstSourceInTable = tableSourceCells[0].textContent;
                //   console.log(parsedJSON[keys[0]]['source']);
                if (firstSourceInTable === parsedJSON[keys[0]]['source']) {
                    let resultHtml = `<table class="table">`;
                    resultHtml +=
                        '<tr><th scope="col">S. No.</th><th scope="col">Source</th><th scope="col">Target</th></tr>';
                    // Iterate over the keys and values in the parsed JSON
                    keys.forEach((key) => {
                        const { source, target } = parsedJSON[key];
                        const escapeSource = source ? escapeHtml(source) : ''; // Use an empty string if source is missing
                        const escapetarget = target ? escapeHtml(target) : ''; // Use an empty string if target is missing
                        // Append a row with the key, source, and target
                        resultHtml += `<tbody><tr><td>${key}</td><td class="source">${escapeSource}</td><td class="target">${escapetarget}</td></tr></tbody>`;

                        // Store the data in the dataToSave object
                        dataToSave[key] = {
                            source,
                            target,
                        };
                    });

                    resultHtml += "</table>";

                    // Append the table to the corresponding container
                    tableDiv.innerHTML = resultHtml;

                    // Save data in local storage
                    const dataToSaveJSON = JSON.stringify(dataToSave);
                    localStorage.setItem(`${projectId}-part${i + 1}`, dataToSaveJSON);
                    $(".chatGPT_step-1, .chatGPT_step-2, .chatGPT_step-3").removeClass(
                        "chatGPT_steps-border"
                    );
                    var errorMsg = "";
                    $(".chatGptError").hide();
                    errorHandling(errorMsg, i);
                    $(".modal-footer.chatGPT-widget-footer .atlt_actions").removeClass(
                        "chatGPT_disable"
                    );
                    $(".chatGPT_save_cont").removeClass("btn-disabled");
                    $(".chatGPT_save_close").removeClass("btn-disabled");
                } else {
                    var errorMsg =
                        "Wrong translated strings! *Please enter right strings translation";
                    $(".chatGptError").show();
                    errorHandling(errorMsg, i);
                    return false;
                }
            } else {
                // Handle cases where the JSON format is neither an array nor an object
                var errorMsg = "Invalid JSON Format! *Please enter a valid JSON-formatted String in the textarea.";
                $(".chatGptError").show();
                errorHandling(errorMsg, i);
                return false;
            }
        }
    }

    function actionsPerPart(parts, locale, projectId, translatedObj, modelContainer) {
        parts.forEach((part, i) => {
            // Convert object into an array with only 'id' and 'source' (for translation input)
            const jsonArray = Object.entries(part).map(([key, value]) => ({
                id: Number(key),  // Convert key to a number
                source: value.trim()  // Trim whitespace for cleaner translation
            }));
        
            const jsonString = JSON.stringify(jsonArray, null, 2);

            const prompt = `
                You are translating a JSON array into **${locale.label}**. Follow these instructions carefully to maintain accuracy and formatting:

                ### **Translation Rules:**
                1. **Preserve Placeholders** – Do **not** translate placeholders like \`%s\`, \`%d\`, \`%S\`, \`%D\`.
                2. **Keep Original IDs** – Retain the \`id\` key as is. If a string is skipped, its \`id\` must still be included.
                3. **Strict JSON Format** – Output must be a **valid JSON array** with **only** \`id\` and \`target\` keys.
                4. **Exclude 'source' Key** – The \`source\` field must not appear in the output.
                5. **Escape Special Characters** – Ensure valid JSON by properly escaping quotes (\`"\` and \`'\`).
                6. **Accurate Translation** – Each string must be correctly translated into **${locale.label}**, even if some translations remain the same.
                7. **Complete Output** – Translate **all ${jsonArray.length} items**, ensuring the exact same count in the final array.

                ---

                ### **Input JSON:**
                \`\`\`json
                ${jsonString}
                \`\`\`

                ### **Expected Output Format:**
                \`\`\`json
                [
                { "id": 1, "target": "(translated text in ${locale.label})" },
                { "id": 2, "target": "(translated text in ${locale.label})" },
                ...
                ]
                \`\`\`

                ### **Final Notes:**
                - **Ensure valid and complete JSON output** without missing or extra entries.
                - **Follow the specified format exactly.** No additional keys or modifications are allowed.
                - **Do not include any explanations**—return only the JSON array.

                `;
                
            document.getElementById(`translate${i}`).value = prompt;

            const copyButton = document.getElementById(`copyButton${i}`);
            copyButton.addEventListener("click", function () {
                $(".chatGPT_step-1, .chatGPT_step-3, .chatGPT_save_cont").removeClass("chatGPT_steps-border");
                $(".chatGPT_step-2").addClass("chatGPT_steps-border");
                const inputField = document.getElementById(`translate${i}`);
                const textArea = document.createElement("textarea");
                textArea.value = inputField.value;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand("copy");
                document.body.removeChild(textArea);
                copyButton.innerText = "Copied";
                setTimeout(() => {
                    window.open('https://chat.openai.com', '_blank');
                }, 800);
                setTimeout(function () {
                    $(".chatGPT_step-2").removeClass("chatGPT_steps-border");
                    $(".chatGPT_step-3").addClass("chatGPT_steps-border");
                }, 1000);
                setTimeout(() => {
                    copyButton.innerText = "Copy";
                }, 6000);
            });

            const textarea = document.getElementById(`output${i}`);
            textarea.addEventListener("input", function (event) {
                setTimeout(function () {
                    const textareaValue = textarea.value;
                    if (textareaValue !== "") {
                        createPartsTable(i, textareaValue, projectId, jsonArray);
                        $(`#clearButton${i}`).show();
                        $(`#preview${i}`).show();
                    }
                    if (textareaValue == "") {
                        var errorMsg = "";
                        $(".chatGptError").hide();
                        $(`#clearButton${i}`).hide();
                        $(`#preview${i}`).hide();
                        $(".modal-footer.chatGPT-widget-footer .atlt_actions").addClass("chatGPT_disable");
                        $(".chatGPT_save_cont").addClass("btn-disabled");
                        $(".chatGPT_save_close").addClass("btn-disabled");
                        errorHandling(errorMsg, i);
                    }
                }, 100);
            });

            //Clear textarea
            const clearTextareaButton = document.getElementById(`clearButton${i}`);
            clearTextareaButton.addEventListener("click", function () {
                $(`#output${i}`).val("");
                $(`#clearButton${i}`).hide();
                $(`#preview${i}`).hide();
                var errorMsg = "";
                $(".chatGptError").hide();
                errorHandling(errorMsg, i);
            });

            //View table of part
            const previewButton = document.getElementById(`preview${i}`);
            previewButton.addEventListener("click", function () {
                $(`#Part${i}`).hide();
                $(`#chatGPT_steps${i}`).hide();
                $(`#prevButton, .notice, .inline, .notice-info, .is-dismissible`).hide();
                $(`#chatGPT_table_close${i}`).show();
                $(`#table${i}`).show();
            });

            // Close preview of table
            const chatGPTTableClose = document.getElementById(`chatGPT_table_close${i}`);
            chatGPTTableClose.addEventListener("click", function () {
                $(`#Part${i}`).show();
                $(`#chatGPT_steps${i}`).show();
                $(`#prevButton, .notice, .inline, .notice-info, .is-dismissible`).show();
                $(`#chatGPT_table_close${i}`).hide();
                $(`#table${i}`).hide();
            });

            //Check if there's data in local storage for last part
            setInterval(function () {
                const savedDataJSON = localStorage.getItem(`${projectId}-part${parts.length}`);
                if ($(".chatGptError").is(":empty") && savedDataJSON) {
                    modelContainer.find(".atlt_stats").fadeIn("slow");
                } else {
                    modelContainer.find(".atlt_stats").hide();
                }
            }, 200); //check every 200ms
        });
    }

    function mergeLocalStorageData(parts, translatedObj) {
        const rpl = {
            '"% s"': '"%s"',
            '"% d"': '"%d"',
            '"% S"': '"%s"',
            '"% D"': '"%d"',
            '% s': ' %s ',
            '% S': ' %s ',
            '% d': ' %d ',
            '% D': ' %d ',
            '٪ s': ' %s ',
            '٪ S': ' %s ',
            '٪ d': ' %d ',
            '٪ D': ' %d ',
            '٪ س': ' %s ',
            '%S': ' %s ',
            '%D': ' %d ',
            '% %': '%%'
        };

        const regex = /(\%\s*\d+\s*\$?\s*[a-z0-9])/gi;

        parts.forEach((part, i) => {
            $(`#table${i} tbody tr`).slice(1).each(function (index) {
                var index = $(this).find("td.source").text();
                var target = $(this).find("td.target").text();
                var source = $(this).find("td.source").text();

                let improvedTarget;
                let improvedSource;
                if ((!(target == '')) && (!(source == ''))) {
                    const improvedTargetrpl = strtr(target, rpl);
                    const improvedSourcerpl = strtr(source, rpl);

                    improvedTarget = improvedTargetrpl.replace(regex, function (match) {
                        return match.replace(/\s/g, '').toLowerCase();
                    });

                    improvedSource = improvedSourcerpl.replace(regex, function (match) {
                        return match.replace(/\s/g, '').toLowerCase();
                    });
                }
                if ((!(improvedTarget == undefined)) && (!(improvedSource == undefined))) {

                    translatedObj.push({
                        source: improvedSource,
                        target: improvedTarget,
                    });
                }
            });
        });
        return translatedObj;
    }

    // parse all translated strings and pass to save function
    function onSaveClick() {
        let translatedObj = [];
        let type = this.getAttribute("data-type");
        let total_character_count = 0;
        let total_word_count = 0;
        const rpl = {
            '"% s"': '"%s"',
            '"% d"': '"%d"',
            '"% S"': '"%s"',
            '"% D"': '"%d"',
            '% s': ' %s ',
            '% S': ' %s ',
            '% d': ' %d ',
            '% D': ' %d ',
            '٪ s': ' %s ',
            '٪ S': ' %s ',
            '٪ d': ' %d ',
            '٪ D': ' %d ',
            '٪ س': ' %s ',
            '%S': ' %s ',
            '%D': ' %d ',
            '% %': '%%'
        };

        const regex = /(\%\s*\d+\s*\$?\s*[a-z0-9])/gi;

        $("." + type + "-widget-body").find(".atlt_strings_table tbody tr").each(function () {
            const source = $(this).find("td.source").text();
            const target = $(this).find("td.target").text();

            const improvedTargetrpl = strtr(target, rpl);
            const improvedSourcerpl = strtr(source, rpl);

            const improvedTarget = improvedTargetrpl.replace(regex, function (match) {
                return match.replace(/\s/g, '').toLowerCase();
            });

            const improvedSource = improvedSourcerpl.replace(regex, function (match) {
                return match.replace(/\s/g, '').toLowerCase();
            });

            total_character_count += improvedSource.length;
            total_word_count += improvedSource.split(/\s+/).length;

            translatedObj.push({
                "source": improvedSource,
                "target": improvedTarget
            });
        });

        const container = $(this).closest('.atlt_custom_model');
        const translationProvider = container.data('translation-provider');
        const translationTime = container.data('translation-time');
        const { lang, region } = locoConf.conf.locale;
        const target_language = region ? `${lang}_${region}` : lang;
        const totalCharacters = translatedObj.reduce((sum, item) => sum + item.source.length, 0);
        const totalStrings = translatedObj.length;

        const translationData = {
            time_taken: translationTime,
            translation_provider: translationProvider,
            character_count: totalCharacters,
            string_count: totalStrings,
            pluginORthemeName: pluginOrThemeName,
            target_language: target_language,
        }

        var projectId = $(this).parents(".atlt_custom_model").find("#project_id").val();

        //  Save Translated Strings
        saveTranslatedStrings(translatedObj, projectId, translationData);
        $(".atlt_custom_model").fadeOut("slow");
        $("html").addClass("merge-translations");
        updateLocoModel();
    }

    // update Loco Model after click on merge translation button
    function updateLocoModel() {
        var checkModal = setInterval(function () {
            var locoModel = $('.loco-modal');
            var locoModelApisBatch = $('.loco-modal #loco-apis-batch');
            if (locoModel.length && // model exists check
                locoModel.attr("style").indexOf("none") <= -1 && // has not display none
                locoModel.find('#loco-job-progress').length // element loaded 
            ) {
                $("html").removeClass("merge-translations");
                locoModelApisBatch.find("a.icon-help, a.icon-group, #loco-job-progress").hide();
                locoModelApisBatch.find("select#auto-api").hide();
                var currentState = $("select#auto-api option[value='loco_auto']").prop("selected", "selected");
                locoModelApisBatch.find("select#auto-api").val(currentState.val());
                locoModel.find(".ui-dialog-titlebar .ui-dialog-title").html("Step 3 - Add Translations into Editor and Save");
                locoModelApisBatch.find("button.button-primary span").html("Start Adding Process");
                locoModelApisBatch.find("button.button-primary").on("click", function () {
                    $(this).find('span').html("Adding...");
                });
                locoModel.addClass("addtranslations");
                $('.noapiadded').remove();
                locoModelApisBatch.find("form").show();
                locoModelApisBatch.removeClass("loco-alert");
                clearInterval(checkModal);
            }
        }, 200); // check every 200ms
    }
    function openTranslationProviderModel(e) {
        if (e.originalEvent !== undefined) {
            var checkModal = setInterval(function () {
                var locoModal = $(".loco-modal");
                var locoBatch = locoModal.find("#loco-apis-batch");
                var locoTitle = locoModal.find(".ui-dialog-titlebar .ui-dialog-title");

                if (locoBatch.length && !locoModal.is(":hidden")) {
                    locoModal.removeClass("addtranslations");
                    locoBatch.find("select#auto-api").show();
                    locoBatch.find("a.icon-help, a.icon-group").show();
                    locoBatch.find("#loco-job-progress").show();
                    locoTitle.html("Auto-translate this file");
                    locoBatch.find("button.button-primary span").html("Translate");

                    var opt = locoBatch.find("select#auto-api option").length;

                    if (opt === 1) {
                        locoBatch.find(".noapiadded").remove();
                        locoBatch.removeClass("loco-alert");
                        locoBatch.find("form").hide();
                        locoBatch.addClass("loco-alert");
                        locoTitle.html("No translation APIs configured");
                        locoBatch.append(`<div class='noapiadded'>
                            <p>Add automatic translation services in the plugin settings.<br>or<br>Use <strong>Auto Translate</strong> addon button.</p>
                            <nav>
                                <a href='http://locotranslate.local/wp-admin/admin.php?page=loco-config&amp;action=apis' class='button button-link has-icon icon-cog'>Settings</a>
                                <a href='https://localise.biz/wordpress/plugin/manual/providers' class='button button-link has-icon icon-help' target='_blank'>Help</a>
                                <a href='https://localise.biz/wordpress/translation?l=de-DE' class='button button-link has-icon icon-group' target='_blank'>Need a human?</a>
                            </nav>
                        </div>`);
                    }
                    clearInterval(checkModal);
                }
            }, 100); // check every 100ms
        }
    }
    // filter string based upon type
    function filterRawObject(rawArray, filterType) {
        return rawArray.filter((item) => {
            if (item.source && !item.target) {
                if (ValidURL(item.source) || isHTML(item.source) || isSpecialChars(item.source) || isEmoji(item.source) || item.source.includes('#')) {
                    return false;
                } else if (isPlacehodersChars(item.source)) {
                    return true;
                } else {
                    return true;
                }
            }
            return false;
        });
    }
    // detect String contain URL
    function ValidURL(str) {
        var pattern = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
        return pattern.test(str);
    }
    // detect Valid HTML in string
    function isHTML(str) {
        var rgex = /<(?=.*? .*?\/ ?>|br|hr|input|!--|wbr)[a-z]+.*?>|<([a-z]+).*?<\/\1>/i;
        return rgex.test(str);
    }
    //  check special chars in string
    function isSpecialChars(str) {
        var rgex = /[@^{}|<>]/g;
        return rgex.test(str);
    }
    //  check Emoji chars in string
    function isEmoji(str) {
        var ranges = [
            '(?:[\u2700-\u27bf]|(?:\ud83c[\udde6-\uddff]){2}|[\ud800-\udbff][\udc00-\udfff]|[\u0023-\u0039]\ufe0f?\u20e3|\u3299|\u3297|\u303d|\u3030|\u24c2|\ud83c[\udd70-\udd71]|\ud83c[\udd7e-\udd7f]|\ud83c\udd8e|\ud83c[\udd91-\udd9a]|\ud83c[\udde6-\uddff]|[\ud83c[\ude01-\ude02]|\ud83c\ude1a|\ud83c\ude2f|[\ud83c[\ude32-\ude3a]|[\ud83c[\ude50-\ude51]|\u203c|\u2049|[\u25aa-\u25ab]|\u25b6|\u25c0|[\u25fb-\u25fe]|\u00a9|\u00ae|\u2122|\u2139|\ud83c\udc04|[\u2600-\u26FF]|\u2b05|\u2b06|\u2b07|\u2b1b|\u2b1c|\u2b50|\u2b55|\u231a|\u231b|\u2328|\u23cf|[\u23e9-\u23f3]|[\u23f8-\u23fa]|\ud83c\udccf|\u2934|\u2935|[\u2190-\u21ff])' // U+1F680 to U+1F6FF
        ];
        return str.match(ranges.join('|'));
    }
    // allowed special chars in plain text
    function isPlacehodersChars(str) {
        var rgex = /%s|%d/g;
        return rgex.test(str);
    }
    // replace placeholders in strings
    function strtr(s, p, r) {
        return !!s && {
            2: function () {
                for (var i in p) {
                    s = strtr(s, i, p[i]);
                }
                return s;
            },
            3: function () {
                return s.replace(RegExp(p, 'g'), r);
            },
            0: function () {
                return;
            }
        }[arguments.length]();
    }

    // Save translated strings in the cache using ajax requests in parts.
    function saveTranslatedStrings(translatedStrings, projectId, translationData) {
        // Check if translatedStrings is not empty and has data
        if (translatedStrings && translatedStrings.length > 0) {
            // Define the batch size for ajax requests
            const batchSize = 2500;

            // Iterate over the translatedStrings in batches
            for (let i = 0; i < translatedStrings.length; i += batchSize) {
                // Extract the current batch
                const batch = translatedStrings.slice(i, i + batchSize);
                // Determine the part based on the batch position
                const part = `-part-${Math.ceil(i / batchSize)}`;
                // Send ajax request for the current batch
                sendBatchRequest(batch, projectId, part, translationData);

            }
        }
    }

    // send ajax request and save data.
    function sendBatchRequest(stringData, projectId, part, translationData) {
        const data = {
            'action': 'save_all_translations',
            'data': JSON.stringify(stringData),
            'part': part,
            'project-id': projectId,
            'wpnonce': nonce,
            'translation_data': JSON.stringify(translationData)
        };
        $.ajax({
            url: ajaxUrl,
            method: 'POST',
            data: data,
            dataType: 'json', // Response data type
            success: function (response) {
                // Handle success
                $('#loco-editor nav button[data-loco="auto"]').trigger("click");
            },
            error: function (xhr, status, error) {
                // Handle error
                console.error(error);
            }
        });
    }

    // integrates auto traslator button in editor
    function addAutoTranslationBtn() {
        // check if button already exists inside translation editor
        const existingBtn = $("#loco-editor nav").find("#cool-auto-translate-btn");
        if (existingBtn.length > 0) {
            existingBtn.remove();
        }
        const locoActions = $("#loco-editor nav").find("#loco-actions");
        const autoTranslateBtn = $('<fieldset><button id="cool-auto-translate-btn" class="button has-icon icon-translate">Auto Translate</button></fieldset>');
        // append custom created button.
        locoActions.append(autoTranslateBtn);
    }
    // open settings model on auto translate button click
    function openSettingsModel() {
        $("#atlt-dialog").dialog({
            dialogClass: rtlClass,
            resizable: false,
            height: "auto",
            width: 400,
            modal: true,
            draggable: false,
            buttons: {
                Cancel: function () {
                    $(this).dialog("close");
                }
            },
        });
    }

    //String Translate Model
    // Get all elements with the class "atlt_custom_model"
    var modals = document.querySelectorAll(".atlt_custom_model");
    // When the user clicks anywhere outside of any modal, close it
    $(window).click(function (event) {
        if (!event.target.closest(".modal-content")) {
            destoryGoogleYandexTranslator();
        }
        for (var i = 0; i < modals.length; i++) {
            var modal = modals[i];
            if ($(event.target).hasClass("atlt_custom_model") && event.target === modal) {
                modal.style.display = "none";
                if ($(".container").length > 0) {
                    // If it exists, remove it
                    if ($(".chatGPT_steps-border").length > 0) {
                        $(".chatGPT_steps-border").removeClass("chatGPT_steps-border");
                    }
                    $(".container").remove();
                }
            }
        }
    });

    // Get the <span> element that closes the modal
    $(".atlt_custom_model").find(".close").on("click", function () {
        destoryGoogleYandexTranslator();
        if ($(".container").length > 0) {
            // If it exists, remove it
            if ($(".chatGPT_steps-border").length > 0) {
                $(".chatGPT_steps-border").removeClass("chatGPT_steps-border");
            }
            $(".container").remove();
        }
        $(".atlt_custom_model").fadeOut("slow");

    });

    function encodeHtmlEntity(str) {
        var buf = [];
        for (var i = str.length - 1; i >= 0; i--) {
            buf.unshift(['&#', str[i].charCodeAt(), ';'].join(''));
        }
        return buf.join('');
    }

    // get object and append inside the popup
    function printStringsInPopup(jsonObj, type) {
        let html = '';
        let totalTChars = 0;
        let index = 1;
        let custom_attr = type === "yandex" ? 'translate="yes"' : '';
        if (jsonObj) {
            for (const key in jsonObj) {
                if (jsonObj.hasOwnProperty(key)) {
                    const element = jsonObj[key];
                    const sourceText = element.source.trim();

                    if (sourceText !== '') {
                        if ((type == "yandex") || type == "google" || type == "deepl" || type == "geminiAI" || type == "openAI" || type == "ChromeAiTranslator") {
                            html += `<tr id="${key}"><td>${index}</td><td class="notranslate source">${encodeHtmlEntity(sourceText)}</td>`;

                            if (type == "yandex" || type == "google" || type == "ChromeAiTranslator") {

                                html += `<td   ${custom_attr}  class="target translate">${sourceText}</td></tr>`;

                            } else {
                                html += '<td class="target translate"></td></tr>';
                            }

                            const div = document.createElement('div');
                            div.innerHTML = sourceText;

                            index++;
                            totalTChars += div.innerText.length;
                        }
                    }
                }
            }

            $(".atlt_stats").each(function () {
                $(this).find(".totalChars").html(totalTChars);
            });
        }

        $("#" + type + '-widget-model').find(".atlt_strings_table > tbody.atlt_strings_body").html(html);

    }

    function settingsModel() {
        const icons = {
            yandex: extradata['yt_preview'],
            google: extradata['gt_preview'],
            deepl: extradata['dpl_preview'],
            chatgpt: extradata['chatGPT_preview'],
            gemini: extradata['geminiAI_preview'],
            openai: extradata['openai_preview'],
            chrome: extradata['chromeAi_preview'],
            docs: extradata['document_preview'],
            error: extradata['error_preview']
        };
    
        const url = 'https://locoaddon.com/docs/';
        const ATLT_IMG = (key) => ATLT_URL + 'assets/images/' + icons[key];
        const DOC_ICON_IMG = `<img src="${ATLT_IMG('docs')}" width="20" alt="Docs">`;
    
        const hasGeminiKey = Array.isArray(apikey) && apikey.includes('google');
        const hasOpenAIKey = Array.isArray(apikey) && apikey.includes('openai');
        const hasDeepLKey = Array.isArray(apikey) && apikey.includes('deepl');
        const rows = [
            {
                name: 'Google Translate',
                icon: 'google',
                info: 'https://translate.google.com/',
                doc: `${url}auto-translations-via-google-translate/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=popup_google_pro`,
                btn: `<button id="atlt_google_translate_btn" class="atlt-provider-btn translate">Translate</button>`
            },
            {
                name: 'Chrome Built-in AI',
                icon: 'chrome',
                info: 'https://developer.chrome.com/docs/ai/translator-api',
                doc: `${url}how-to-use-chrome-ai-auto-translations/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=popup_chrome_pro`,
                btn: `
                    <button id="ChromeAiTranslator_settings_btn" class="atlt-provider-btn translate">Translate</button>
                    <button id="atlt-chromeai-disabled-message" class="atlt-provider-btn error d-none">
                        <img src="${ATLT_IMG('error')}" alt="error" style="height:16px; vertical-align:middle; margin-right:5px;">
                        View Error
                    </button>
                    <div id="atlt-chromeai-disabled-message-content" style="display:none;"></div>
                `
            },
            {
                name: 'Yandex Translate',
                icon: 'yandex',
                info: 'https://translate.yandex.com/',
                doc: `${url}translate-plugin-theme-via-yandex-translate/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=popup_yandex`,
                btn: `<button id="atlt_yandex_translate_btn" class="atlt-provider-btn translate">Translate</button>`
            },
            {
                name: 'ChatGPT Translate',
                icon: 'chatgpt',
                info: 'https://chat.openai.com/',
                doc: `${url}chatgpt-ai-translations-wordpress/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=popup_chatgpt_pro`,
                btn: `<button id="atlt_chatGPT_btn" class="atlt-provider-btn translate">Translate</button>`
            },
            {
                name: 'Gemini AI Translate',
                icon: 'gemini',
                info: 'https://locoaddon.com/docs/pro-plugin/how-to-use-gemini-ai-to-translate-plugins-or-themes/',
                doc: `${url}gemini-ai-translations-wordpress/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=popup_gemini_pro`,
                btn: hasGeminiKey
                    ? `<button id="atlt_geminiAI_btn" class="atlt-provider-btn translate">Translate</button>`
                    : `<button class="atlt-provider-btn atlt_addApikey_btn error notranslate add-api-btn">
                        <img src="${ATLT_IMG('error')}" alt="error" style="height:16px; vertical-align:middle; margin-right:5px;">Add API key
                       </button>`
            },
            {
                name: 'OpenAI Translate',
                icon: 'openai',
                info: 'https://locoaddon.com/docs/pro-plugin/how-to-use-gemini-ai-to-translate-plugins-or-themes/',
                doc: `${url}gemini-ai-translations-wordpress/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=popup_openai_pro`,
                btn: hasOpenAIKey
                    ? `<button id="atlt_openai_btn" class="atlt-provider-btn translate">Translate</button>`
                    : `<button class="atlt-provider-btn atlt_addApikey_btn error notranslate add-api-btn">
                        <img src="${ATLT_IMG('error')}" alt="error" style="height:16px; margin-right:5px; vertical-align:middle;">Add API key
                       </button>`
            },
            {
                name: 'DeepL Translate',
                icon: 'deepl',
                info: 'https://www.deepl.com/translator',
                doc: `${url}translate-via-deepl-translator/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=popup_deepl_pro`,
                btn: hasDeepLKey
                    ? `<button id="atlt_deepl_btn" class="atlt-provider-btn translate">Translate</button>`
                    : `<button class="atlt-provider-btn atlt_addApikey_btn error notranslate add-api-btn">
                        <img src="${ATLT_IMG('error')}" alt="error" style="height:16px; margin-right:5px; vertical-align:middle;">Add API key
                       </button>`
            },
        ];
    
        const rowHTML = rows.map(row => `
            <tr>
                <td class="atlt-provider-name">
                    <a href="${row.info}" target="_blank">
                        <img src="${ATLT_IMG(row.icon)}" class="atlt-provider-icon" alt="${row.name}">
                    </a>
                    ${row.name}
                </td>
                <td>${row.btn}</td>
                <td>
                    <a href="${row.doc}" target="_blank" class="atlt-provider-docs-btn">${DOC_ICON_IMG}</a>
                </td>
            </tr>
        `).join('');
    
        const modelHTML = `
            <div class="atlt-provider-modal" id="atlt-dialog" title="Step 2 - Select Translation Provider" style="display:none;">
                <table class="atlt-provider-table">
                    <thead>
                        <tr><th>Name</th><th>Translate</th><th>Docs</th></tr>
                    </thead>
                    <tbody>${rowHTML}</tbody>
                </table>
            </div>
        `;
    
        $("body").append(modelHTML);
    }
    

    // modal to show strings
    function createStringsModal(projectId, widgetType) {
        // Set wrapper, header, and body classes based on widgetType
        let { wrapperCls, headerCls, bodyCls, footerCls, modelId } = getWidgetClasses(widgetType);

        let modelHTML = `
            <div id="${modelId}" class="modal atlt_custom_model  ${wrapperCls} ${rtlClass}">
                <div class="modal-content">
                    <input type="hidden" id="project_id" value="${projectId}"> 
                    ${modelHeaderHTML(widgetType, headerCls)}   
                    ${modelBodyHTML(widgetType, bodyCls)}   
                    ${modelFooterHTML(widgetType, footerCls)} 
                    </div>
                </div>`;

        $("body").append(modelHTML);
    }

    // Get widget classes based on widgetType
    function getWidgetClasses(widgetType) {
        let wrapperCls = '';
        let headerCls = '';
        let bodyCls = '';
        let footerCls = '';
        let modelId = '';
        switch (widgetType) {
            case 'yandex':
                wrapperCls = 'yandex-widget-container';
                headerCls = 'yandex-widget-header';
                bodyCls = 'yandex-widget-body';
                footerCls = 'yandex-widget-footer';
                modelId = 'yandex-widget-model';
                type = 'yandex';
                break;
            case 'google':
                wrapperCls = 'google-widget-container';
                headerCls = 'google-widget-header';
                bodyCls = 'google-widget-body';
                footerCls = 'google-widget-footer';
                modelId = 'google-widget-model';
                type = 'google';
                break;
            case 'deepl':
                wrapperCls = 'deepl-widget-container';
                headerCls = 'deepl-widget-header';
                bodyCls = 'deepl-widget-body';
                footerCls = 'deepl-widget-footer';
                modelId = 'deepl-widget-model';
                type = 'deepl';
                break;
            case 'ChromeAiTranslator':
                wrapperCls = 'ChromeAiTranslator-widget-container';
                headerCls = 'ChromeAiTranslator-widget-header';
                bodyCls = 'ChromeAiTranslator-widget-body';
                footerCls = 'ChromeAiTranslator-widget-footer';
                modelId = 'ChromeAiTranslator-widget-model';
                type = 'ChromeAiTranslator';
                break;
            case 'chatGPT':
                wrapperCls = 'chatGPT-widget-container';
                headerCls = 'chatGPT-widget-header';
                bodyCls = 'chatGPT-widget-body';
                footerCls = 'chatGPT-widget-footer';
                modelId = 'chatGPT-widget-model';
                type = 'chatGPT';
                break;
            case 'geminiAI':
                wrapperCls = 'geminiAI-widget-container';
                headerCls = 'geminiAI-widget-header';
                bodyCls = 'geminiAI-widget-body';
                footerCls = 'geminiAI-widget-footer';
                modelId = 'geminiAI-widget-model';
                type = 'geminiAI';
                break;
            case 'openAI':
                wrapperCls = 'openAI-widget-container';
                headerCls = 'openAI-widget-header';
                bodyCls = 'openAI-widget-body';
                footerCls = 'openAI-widget-footer';
                modelId = 'openAI-widget-model';
                type = 'openAI';
                break;
            default:
                // Default class if widgetType doesn't match any case
                wrapperCls = 'yandex-widget-container';
                headerCls = 'yandex-widget-header';
                bodyCls = 'yandex-widget-body';
                footerCls = 'yandex-widget-footer';
                break;
        }
        return { wrapperCls, headerCls, bodyCls, footerCls, modelId, type };
    }
    function modelBodyHTML(widgetType, bodyCls) {
        const translator_type = `${type}`;
        const capitalizedString = capitalizeFirstLetter(translator_type);
        function capitalizeFirstLetter(str) {
            str = str.replace('ChromeAiTranslator', 'Chrome AI');
            str = str.replace('deepl', 'DeepL');
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
        const HTML = `<div class = "modal-scrollbar">
        <div class="notice inline notice-info is-dismissible">
                        Plugin will not translate any strings with HTML or special characters because ${capitalizedString} Translator currently does not support HTML and special characters translations.
                        You can edit translated strings inside Loco Translate Editor after merging the translations. Only special characters (%s, %d) fixed at the time of merging of the translations.
                    </div>
                    <div class="notice inline notice-info is-dismissible">
                        Machine translations are not 100% correct.
                        Please verify strings before using on the production website.
                    </div>
        <div class="modal-body  ${bodyCls}">
            <div class="atlt_translate_progress">
                Automatic translation is in progress....<br/>
                It will take a few minutes, enjoy ☕ coffee in this time!<br/><br/>
                Please do not leave this window or browser tab while the translation is in progress...

            <div class="progress-wrapper">
                <div class="progress-container">
                    <div class="progress-bar" id="myProgressBar">
                        <span id="progressText">0%</span>
                    </div>
                </div>
            </div>
            </div>
            <div class="atlt_translate_warning-massage">
                <div class="warning-massage-wrapper">
                     <button class="close-button">&times;</button>
                     <div class="warning-massage-content"></div>
                </div>
            </div>
            ${translatorWidget(widgetType)}
            <div class="atlt_string_container">
                <table class="scrolldown atlt_strings_table">
                    <thead>
                        <th class="notranslate">S.No</th>
                        <th class="notranslate">Source Text</th>
                        <th class="notranslate">Translation</th>
                    </thead>
                    <tbody class="atlt_strings_body">
                    </tbody>
                </table>
            </div>
            <div class="notice-container"></div>
        </div>
        </div>`;
        return HTML;
    }

    function modelHeaderHTML(widgetType, headerCls) {
        if (widgetType === "yandex" || widgetType === "google" || widgetType === "deepl" || widgetType === "openAI"|| widgetType === "geminiAI" || widgetType === "ChromeAiTranslator") {
            const HTML = `
        <div class="modal-header  ${headerCls}">
                        <span class="close">&times;</span>
                        <h2 class="notranslate">Step 2 - Start Automatic Translation Process</h2>
                        <div class="atlt_actions">
                            <button class="notranslate atlt_save_strings button button-primary" data-type = "${type}" disabled="true">Merge Translation</button>
                        </div>
                        <div style="display:none" class="atlt_stats hidden">
                            Wahooo! You have saved your valuable time via auto translating 
                            <strong class="totalChars"></strong> characters  using 
                            <strong>
                                <a href="https://wordpress.org/support/plugin/automatic-translator-addon-for-loco-translate/reviews/#new-post" target="_new">
                                    LocoAI – Auto Translate for Loco Translate (Pro)
                                </a>
                            </strong>
                        </div>
                    </div>
                    `;
            return HTML;
        } else if (widgetType === "chatGPT") {
            const HTML = `
        <div class="modal-header  ${headerCls}">
                        <span class="close">&times;</span>
                        <h2 class="notranslate">Step 2 - Start Automatic Translation Process</h2>
                        <div class="atlt_actions">
                            <button class="notranslate atlt_save_strings button button-primary" data-type = "${type}" disabled="true">Merge Translation</button>
                        </div>
                        <div style="display:none" class="atlt_stats hidden">
                            Wahooo! You have saved your valuable time via auto translating 
                            <strong class="totalChars"></strong> characters  using 
                            <strong>
                                <a href="https://wordpress.org/support/plugin/automatic-translator-addon-for-loco-translate/reviews/#new-post" target="_new">
                                    LocoAI – Auto Translate for Loco Translate (Pro)
                                </a>
                            </strong>
                        </div>
                    </div>
                    `;
            return HTML;
        }

    }
    function modelFooterHTML(widgetType, footerCls) {

        if (widgetType === "yandex" || widgetType === "google" || widgetType === "deepl" || widgetType === "geminiAI" || widgetType === "openAI" || widgetType === "ChromeAiTranslator") {
            const HTML = ` <div class="modal-footer ${footerCls}">
        <div class="atlt_actions">
            <button class="notranslate atlt_save_strings button button-primary" data-type = "${type}" disabled="true">Merge Translation</button>
        </div>
        <div style="display:none" class="atlt_stats">
            Wahooo! You have saved your valuable time via auto translating 
            <strong class="totalChars"></strong> characters  using 
            <strong>
                <a href="https://wordpress.org/support/plugin/automatic-translator-addon-for-loco-translate/reviews/#new-post" target="_new">
                    LocoAI – Auto Translate for Loco Translate (Pro)
                </a>
            </strong>
        </div>
    </div>`;
            return HTML;
        } else if (widgetType === "chatGPT") {
            const HTML = ` <div class="modal-footer ${footerCls}">
            <div class="atlt_actions chatGPT_disable">
            <button data-type = "${type}" class="chatGPT_save_cont button button-primary btn-disabled">Save & Continue</button>
            <button data-type = "${type}" class="chatGPT_save_close button button-primary btn-disabled">Save & Close</button>
        </div>
        <div class="chatGptError"></div>
        <div style="display:none" class="atlt_stats">
            Wahooo! You have saved your valuable time via auto translating 
            <strong class="totalChars"></strong> characters  using 
            <strong>
                <a href="https://wordpress.org/support/plugin/automatic-translator-addon-for-loco-translate/reviews/#new-post" target="_new">
                    LocoAI – Auto Translate for Loco Translate (Pro)
                </a>
            </strong>
        </div>
        </div>`;
            return HTML;
        } else {
            return '';
        }
    }

    // Translator widget HTML
    function translatorWidget(widgetType) {
        if (widgetType === "yandex") {
            const widgetPlaceholder = '<div id="ytWidget">..Loading</div><br>';
            return `<div class="translator-widget  ${widgetType}">
                    <h3 class="choose-lang">Choose language <span class="dashicons-before dashicons-translation"></span></h3>
                    ${widgetPlaceholder}
                </div>`;
        } else if (widgetType === "google") {
            const widgetPlaceholder = '<div id="google_translate_element"></div>';
            return `<div class="translator-widget  ${widgetType}">
                    <h3 class="choose-lang">Choose language <span class="dashicons-before dashicons-translation"></span></h3>
                    ${widgetPlaceholder}
                </div>`;
        } else if (widgetType === "ChromeAiTranslator") {
            return `<div class="translator-widget  ${widgetType}">
                    <h3 class="choose-lang">Translate Using Chrome Built-in AI<div class="atlt_chrome_ai"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"><path fill="none" stroke="#5cb85c" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m10 7l-.516 1.394c-.676 1.828-1.014 2.742-1.681 3.409s-1.581 1.005-3.409 1.681L3 14l1.394.516c1.828.676 2.742 1.015 3.409 1.681s1.005 1.581 1.681 3.409L10 21l.516-1.394c.676-1.828 1.015-2.742 1.681-3.409s1.581-1.005 3.409-1.681L17 14l-1.394-.516c-1.828-.676-2.742-1.014-3.409-1.681s-1.005-1.581-1.681-3.409zm8-4l-.221.597c-.29.784-.435 1.176-.72 1.461c-.286.286-.678.431-1.462.72L15 6l.598.221c.783.29 1.175.435 1.46.72c.286.286.431.678.72 1.462L18 9l.221-.597c.29-.784.435-1.176.72-1.461c.286-.286.678-.431 1.462-.72L21 6l-.598-.221c-.783-.29-1.175-.435-1.46-.72c-.286-.286-.431-.678-.72-1.462z" color="#5cb85c"/></svg></div></h3>
                     <div id="chrome_ai_translator_element"></div>
                </div>`;
        } else if (widgetType === "openAI") {
            const widgetPlaceholder = `
                <div id="openAI_translate_element">
                    <button id="openAI_translate_button" class="button button-primary">Translate with OpenAI</button>
                </div>`;
            return `<div class="translator-widget  ${widgetType}">
                    <h3 class="choose-lang">Translate Using OpenAI <span class="dashicons-before dashicons-translation"></span></h3>
                    ${widgetPlaceholder}
                </div>`;
        }else if (widgetType === "geminiAI") {
            const widgetPlaceholder = `
                <div id="geminiAI_translate_element">
                    <button id="geminiAI_translate_button" class="button button-primary">Translate with GeminiAI</button>
                </div>`;
            return `<div class="translator-widget  ${widgetType}">
                    <h3 class="choose-lang">Translate Using GeminiAI <span class="dashicons-before dashicons-translation"></span></h3>
                    ${widgetPlaceholder}
                </div>`;
        } else if (widgetType === "deepl") {
            const widgetPlaceholder = `
            <div id="deepl_translate_element">
                <button id="deepl_translate_button" class="button button-primary">Translate with DeepL</button>
            </div>`;
        return `<div class="translator-widget  ${widgetType}">
                <h3 class="choose-lang">Translate Using DeepL <span class="dashicons-before dashicons-translation"></span></h3>
            ${widgetPlaceholder}
            </div>`;
        } else if (widgetType === "chatGPT") {
            currentTab = 0;
            const widgetPlaceholder = '<div id="chatGPT_translate_element"></div>';
            return `<div class="translator-widget  ${widgetType}">
            <div class="chatGPT_progress">
          <div id="chatGPT_progressbar" style="width: 0%"></div>
        </div>
            <form id="Form">
            <div id="tabContainer">
            <button style="float:right; font-size:14px;" class="button button-primary prev_btn" id="prevButton" disabled="true" type="button">&#8249; Previous</button>
            </div>
            </form>
                ${widgetPlaceholder}
            </div>`;
        } else {
            return ''; // Return an empty string for non-yandex widget types
        }
    }
    // oninit
    $(document).ready(function () {
        initialize();
    });

})(window, jQuery, gTranslateWidget);


