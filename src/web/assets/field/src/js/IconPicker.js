/** global: Craft */
/** global: Garnish */
/**
 * Icon Picker
 */
Craft.IconifyPicker = Craft.BaseInputGenerator.extend(
    {
        $container: null,
        $preview: null,
        $chooseBtn: null,
        $removeBtn: null,
        $inputName: null,
        $inputSet: null,

        currentPage: 1,
        modal: null,
        cancelToken: null,
        $searchInput: null,
        $iconListContainer: null,
        $iconList: null,
        $affixOptions: null,
        $affixOptionsContainer:null,

        get listLength() {
            return this.$iconList.find('li').length;
        },

        init(container, settings) {
            this.$container = $(container);
            this.setSettings(settings, Craft.IconifyPicker.defaults);
            this.hasMore = true;
            this.loading = false;
            this.$preview = this.$container.children('.icon-picker--icon');
            this.$chooseBtn = this.$container.children('.icon-picker--choose-btn');
            this.$removeBtn = this.$container.children('.icon-picker--remove-btn');
            const fieldName = settings.fieldsNamePrefix + '[name]';
            const fieldSet = settings.fieldsNamePrefix + '[set]';
            this.$inputName = this.$container.find(`input[name="${fieldName}"]`);
            this.$inputSet = this.$container.find(`input[name="${fieldSet}"]`);
            this.addListener(this.$chooseBtn, 'activate', () => {
                this.showModal();
            });

            this.addListener(this.$removeBtn, 'activate', () => {
                this.removeIcon();
            });
        },
        cleanState() {
            this.currentPage = 1;
            this.hasMore = true;
        },
        showModal() {
            if (!this.modal) {
                this.createModal();
            } else {
                this.modal.show();
            }
        },

        createModal() {
            const $container = $('<div class="modal icon-picker-modal"/>');
            const $body = $('<div class="body"/>').appendTo($container);

            const $searchContainer = $('<div class="texticon"/>').appendTo($body);
            $(
                '<span class="texticon-icon search icon" aria-hidden="true"/>'
            ).appendTo($searchContainer);
            this.$searchInput = Craft.ui
                .createTextInput({
                    name: 'search',
                    class: 'clearable',
                    placeholder: Craft.t('app', 'Search'),
                })
                .attr('aria-label', Craft.t('app', 'Search'))
                .appendTo($searchContainer);
            const $clearBtn = $('<button/>', {
                class: 'clear-btn hidden',
                title: Craft.t('app', 'Clear search'),
                'aria-label': Craft.t('app', 'Clear search'),
            }).appendTo($searchContainer);

            this.$iconListContainer = $(
                '<div class="icon-picker-modal--list"/>'
            ).appendTo($body);
            this.$iconList = $('<ul tabindex="-1"/>').appendTo(
                this.$iconListContainer
            );

            this.updateLangAttribute(this.$iconList);
            const $spinner = $('<div class="spinner spinner-absolute"/>').appendTo(
                this.$iconListContainer
            );

            const $footer = $(
                '<div class="flex mb-2"/>'
            ).appendTo($body);

            this.$setOptions = Craft.ui
                .createSelect({
                    id: 'iconSet',
                    name: 'iconSet',
                    options: this.settings.iconSets,
                    value: this.settings.defaultSet
                })
                .appendTo($footer);

             this.$affixOptionsContainer = Craft.ui.createSelect({
                id: 'affixOptions',
                name: 'affixOptions',
                options: [],
                class: 'hidden'
            }).appendTo($footer);
            this.$affixOptions = this.$affixOptionsContainer.children('#affixOptions');

            this.$iconList.on('scroll.infiniteScroll', $.proxy(this.onScroll, this));

            Craft.cp.announce(Craft.t('app', 'Loading'));
            const formObserver = new Craft.FormObserver($searchContainer, () => {
                this.updateIcons();
            });

            this.addListener(this.$setOptions, 'select,change', () =>{
                this.$affixOptions.val('');
                this.cleanState();
                this.updateIcons();
                this.$iconList.scrollTop(0);
            });

            this.addListener(this.$affixOptions, 'select,change', () =>{
                this.cleanState();
                this.updateIcons();
                this.$iconList.scrollTop(0);
            });

            this.addListener(this.$searchInput, 'input,change', () => {
                if (this.$searchInput.val()) {
                    $clearBtn.removeClass('hidden');
                } else {
                    $clearBtn.addClass('hidden');
                }
            });

            this.addListener($clearBtn, 'activate', () => {
                this.$searchInput.val('').trigger('change');
                formObserver.checkForm();
            });

            this.addListener(this.$iconList, 'click', (ev) => {
                let $button;
                if (ev.target.nodeName === 'BUTTON') {
                    $button = $(ev.target);
                } else {
                    $button = $(ev.target).closest('button');
                    if (!$button.length) {
                        return;
                    }
                }

                this.selectIcon($button);
            });

            this.modal = new Garnish.Modal($container);
            this.updateIcons();
        },

        async onScroll() {
            if (this.loading || !this.hasMore) return;
            const listElement = this.$iconList[0];
            const scrollTop = listElement.scrollTop;
            const scrollHeight = listElement.scrollHeight;
            const clientHeight = listElement.clientHeight;
            if (scrollTop + clientHeight >= scrollHeight - 200) {
                this.loadMore();
            }
        },
        async updateIcons() {
            const listHtml = await this.loadIcons();
            this.$iconList.html(listHtml);
            const message = `${Craft.t('app', 'Loading complete')} - ${Craft.t(
                'app',
                '{num, number} {num, plural, =1{result} other{results}}',
                {
                    num: this.listLength,
                }
            )}`;

            Craft.cp.announce(message);
        },

        async loadMore() {
            this.currentPage += 1;
            const listHtml = await this.loadIcons();
            if (listHtml.length <= 0) {
                this.hasMore = false;
                return;
            }
            this.$iconList.append(listHtml);
        },

        async loadIcons() {
            if (this.cancelToken) {
                this.cancelToken.cancel();
            }

            this.loading = true;
            const search = this.$searchInput.val();
            const set =  $('#iconSet').val();
            const affix = this.$affixOptions.val();

            this.$iconListContainer.addClass('loading');
            Craft.cp.announce(Craft.t('app', 'Loading'));
            this.cancelToken = axios.CancelToken.source();

            try {

                const response = await Craft.sendActionRequest(
                    'POST',
                    'iconify/field/picker',
                    {
                        data: {
                            search,
                            set,
                            affix,
                            page: this.currentPage,
                        },
                        cancelToken: this.cancelToken.token,
                    }
                );
                const listHtml = response.data.listHtml;
                this.$affixOptions.empty();
                if (Object.keys(response.data.affixOptions).length > 1) {
                    // this.$affixOptions
                    $('<option/>', {
                        value: '',
                        text: response.data.affixOptions['']
                    }).appendTo(this.$affixOptions);
                    $.each(response.data.affixOptions, (key, label) => {
                        if (key !== '') {
                            $('<option/>', {
                                value: key,
                                text: label,
                                selected: key === response.data.selectedAffix
                            }).appendTo(this.$affixOptions);
                        }
                    });
                    this.$affixOptionsContainer.removeClass('hidden');
                } else {
                    this.$affixOptionsContainer.addClass('hidden')
                }

                return listHtml;
            } finally {
                this.$iconListContainer.removeClass('loading');
                this.cancelToken = null;
                this.loading = false;
            }
        },

        updateLangAttribute($element) {
            const htmlLang = document.documentElement.lang;

            if (!htmlLang.startsWith('en')) {
                $element.attr('lang', 'en');
            }
        },

        selectIcon($button) {
            this.modal.hide();
            const name = $button.attr('title');
            const iconName = $button.attr('data-iconName');
            const iconSet = $button.attr('data-iconSet');
            this.$preview
                .html($button.html())
                .attr('title', name)
                .attr('aria-label', name)
                .attr('role', 'img');

            this.updateLangAttribute(this.$preview);
            this.$inputName.val(iconName);
            this.$inputSet.val(iconSet);
            this.$chooseBtn.children('.label').text(Craft.t('app', 'Change'));
            this.$chooseBtn.focus();
            this.$removeBtn.removeClass('hidden');
            if (this.$container.hasClass('small')) {
                this.$chooseBtn.addClass('hidden');
            }
        },

        removeIcon() {
            this.$preview.html('').removeAttr('title').removeAttr('aria-label');
            this.$input.val('');
            this.$chooseBtn.children('.label').text(Craft.t('app', 'Choose'));
            this.$removeBtn.addClass('hidden');
            if (this.$container.hasClass('small')) {
                this.$chooseBtn.removeClass('hidden');
                this.$chooseBtn.focus();
            } else {
                this.$chooseBtn.focus();
            }
        },
    },
    {
        defaults: {
            iconSets: {},
        },
    }
);