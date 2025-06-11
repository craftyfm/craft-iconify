Craft.IconifyCustomize = Craft.BaseInputGenerator.extend(
    {
        $container: null,
        $preview: null,
        $inputColor: null,
        $inputStrokeWidth: null,
        $storkeWidthText: null,

        $modalInputColor: null,
        $modalInputStrokeWidth: null,
        $svgContainer: null,
        $strokeInfo: null,
        modal: null,
        $svg: null,


        init(container, settings) {
            this.$container = $(container);
            this.setSettings(settings, Craft.IconifyCustomize.defaults);
            this.$preview = this.$container.children('.icon-picker--icon');
            const fieldColor = settings.fieldsNamePrefix + '[color]';
            const fieldStroke = settings.fieldsNamePrefix + '[strokeWidth]';
            this.$inputColor = this.$container.find(`input[name="${fieldColor}"]`);
            this.$inputStrokeWidth = this.$container.find(`input[name="${fieldStroke}"]`);
            this.addListener(this.$preview, 'activate', () => {
                this.showModal();
            });
        },

        showModal() {
            const svg = this.$preview.html();
            if (!svg || !svg.trim()) {
                return;
            }
            if (!this.modal) {
                this.createModal();
            } else {
                this.triggerModal();
            }

        },
        triggerModal() {
            const color = this.$inputColor.val().replace(/^#/, '');
            const $svg = $(this.$preview.html());
            const strokeWidth = this.$inputStrokeWidth.val();
            this.$svg = $svg.get(0);
            this.$svgContainer.html($svg);
            this.$modalInputColor.val(color);
            this.$modalInputStrokeWidth.val(strokeWidth ? strokeWidth : 0);
            this.$strokeInfo.text(`${strokeWidth ? strokeWidth : 0} px`)
            this.updateIconColor(color);
            this.updateIconStrokeWidth(strokeWidth)
            this.modal.show();
        },
        createModal() {
            const color = this.$inputColor.val().replace(/^#/, '');
            const strokeWidth = this.$inputStrokeWidth.val();
            const $container = $('<div class="modal craftyfm-iconify-customize-modal"/>');
            const $body = $('<div class="body"/>').appendTo($container);
            const $svgPreview = $('<div class="craftyfm-iconify-svg-preview" />').appendTo($body);
            this.$svgContainer = $('<div class="craftyfm-iconify-svg-container" />').appendTo($svgPreview);
            const $svg = $(this.$preview.html());
            this.$svg = $svg.get(0);
            this.$svgContainer.html($svg);
            const $controlPanel = $('<div class="craftyfm-iconify-controls-section"/>').appendTo($body);

            // color
            const $colorGroup = $('<div class="craftyfm-iconify-control-group"/>').appendTo($controlPanel);
            $('<label class="craftyfm-iconify-control-label">Icon Color</label>').appendTo($colorGroup);
            const $colorInputWrapper = $('<div class="color-input-wrapper"/>').appendTo($colorGroup);

            const $colorContainer = Craft.ui.createColorInput({
                name: 'colorInput',
                value: color,
            }).appendTo($colorInputWrapper);
            this.$modalInputColor = $colorContainer.find('input.color-input');

            // stroke width
            const $strokeGroup = $('<div class="craftyfm-iconify-control-group"/>').appendTo($controlPanel);
            $('<label class="craftyfm-iconify-control-label">Stroke Width</label>').appendTo($strokeGroup);
            this.$modalInputStrokeWidth = $(`<input type="range" id="strokeInput" 
                    class="stroke-input" min="0" max="4" step="0.1" value="${strokeWidth ? strokeWidth: 0 }"/>`)
                .appendTo($strokeGroup);
            this.$strokeInfo = $('<div/>').appendTo($strokeGroup);
            this.$strokeInfo.text(`${this.settings.strokeWidth ? this.settings.strokeWidth : 0} px`);

            // button apply
            const $footer = $('<div class="rightalign"/>').appendTo($body)
            const $applyButton = Craft.ui.createButton({
                type: 'button',
                label: 'Apply',
            }).appendTo($footer);

            this.addListener(this.$modalInputColor, 'input,change', () => {
                this.updateIconColor(`#${this.$modalInputColor.val()}`);
            });

            const colorObserver = new Craft.FormObserver($colorContainer, () => {
                this.updateIconColor(`#${this.$modalInputColor.val()}`);
            });


            this.addListener(this.$modalInputStrokeWidth, 'input,change', () => {
                this.updateIconStrokeWidth(this.$modalInputStrokeWidth.val());
            });

            this.addListener($applyButton, 'click', ev => {
                ev.preventDefault();
                this.applyChanges()
            })

            this.updateIconColor(`#${color}`);
            this.updateIconStrokeWidth(strokeWidth);
            this.modal = new Garnish.Modal($container);
        },
        updateIconColor(color) {
            const isValidHex = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(color);
            if (isValidHex) {
                this.$svg.setAttribute('stroke', color);
                const paths = this.$svg.querySelectorAll('path[fill]');
                paths.forEach(path => {
                    path.setAttribute('fill', color);
                });
            } else {
                this.$svg.setAttribute('stroke', 'currentColor');
                const paths = this.$svg.querySelectorAll('path[fill]');
                paths.forEach(path => {
                    path.setAttribute('fill', 'currentColor');
                });
            }
        },
        updateIconStrokeWidth(width) {
            this.$svg.setAttribute('stroke-width', width ? width : 0);
            this.$strokeInfo.text(`${width ? width : 0}px`);
        },
        applyChanges() {
            this.$inputColor.val(`#${this.$modalInputColor.val()}`);
            this.$inputStrokeWidth.val(this.$modalInputStrokeWidth.val());
            this.modal.hide();
        }
    },
    {
        defaults: {
        }
    }
);