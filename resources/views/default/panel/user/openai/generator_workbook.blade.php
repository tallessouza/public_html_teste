@php
    $creativity_levels = [
        '0.25' => 'Economic',
        '0.5' => 'Average',
        '0.75' => 'Good',
        '1' => 'Premium',
    ];

    $voice_tones = ['Professional', 'Funny', 'Casual', 'Excited', 'Witty', 'Sarcastic', 'Feminine', 'Masculine', 'Bold', 'Dramatic', 'Grumpy', 'Secretive'];

    $youtube_actions = [
        'blog' => 'Prepare a Blog Post',
        'short' => 'Explain the Main Idea',
        'list' => 'Create a List',
        'tldr' => 'Create TLDR',
        'prons_cons' => 'Prepare Pros and Cons',
    ];
@endphp

@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __($openai->title))
@section('titlebar_subtitle')
    @if ($openai->type == 'code')
        {{ __('Generate high quality code in seconds.') }}
    @elseif(isset($openai->description))
        {{ __($openai->description) }}
    @endif
@endsection

@section('content')
    <div class="py-10">
        <div
            class="lqd-generator-wrap grid grid-flow-row gap-y-8 lg:grid-flow-col lg:[grid-template-columns:41%_59%]"
            data-generator-type="{{ $openai->type }}"
        >
            <div class="flex w-full flex-col gap-6 lg:pe-14">
                <x-card class="lqd-generator-remaining-credits">
                    <h5 class="mb-3 text-xs font-normal">
                        {{ __('Remaining Credits') }}
                    </h5>

                    <x-remaining-credit
                        class="flex-col-reverse text-xs"
                        style="inline"
                    />
                </x-card>

                <x-card
                    class="lqd-generator-options-card"
                    variant="{{ Theme::getSetting('defaultVariations.card.variant', 'outline') === 'outline' ? 'none' : Theme::getSetting('defaultVariations.card.variant', 'solid') }}"
                    size="{{ Theme::getSetting('defaultVariations.card.variant', 'outline') === 'outline' ? 'none' : Theme::getSetting('defaultVariations.card.size', 'md') }}"
                    roundness="{{ Theme::getSetting('defaultVariations.card.roundness', 'default') === 'default' ? 'none' : Theme::getSetting('defaultVariations.card.roundness', 'default') }}"
                >
                    <form
                        class="lqd-generator-form flex flex-wrap justify-between gap-y-5"
                        id="openai_generator_form"
                        onsubmit="return sendOpenaiGeneratorForm();"
                    >

                        @if ($openai->type != 'code')
                            <div
                                class="flex w-full flex-col gap-5"
                                x-data="{ 'brandEnabled': false }"
                            >
                                <x-forms.input
                                    id="brand"
                                    type="checkbox"
                                    name="brand"
                                    label="{{ __('Include Your Brand') }}"
                                    @change="brandEnabled = $el.checked"
                                    switcher
                                />

                                <div
                                    class="hidden w-full flex-col gap-5"
                                    :class="{ 'hidden': !brandEnabled, 'flex': brandEnabled }"
                                >
                                    <x-forms.input
                                        id="company"
                                        size="lg"
                                        type="select"
                                        label="{{ __('Select Company') }}"
                                        name="company"
                                    >
                                        <x-slot:label-extra>
                                            <a
                                                class="size-6 inline-flex items-center justify-center rounded-lg bg-green-500/20 text-green-700 transition-all hover:scale-110 hover:bg-green-500 hover:text-green-100"
                                                href="{{ LaravelLocalization::localizeUrl(route('dashboard.user.brand.edit')) }}"
                                            >
                                                <x-tabler-plus class="size-4" />
                                            </a>
                                        </x-slot:label-extra>
                                        <option value="">
                                            {{ __('Select Company') }}
                                        </option>
                                        @foreach (auth()->user()->getCompanies() as $company)
                                            <option
                                                data-tone_of_voice="{{ $company->tone_of_voice }}"
                                                value="{{ $company->id }}"
                                            >
                                                {{ $company->name }}
                                            </option>
                                        @endforeach
                                    </x-forms.input>

                                    <x-forms.input
                                        id="product"
                                        name="product"
                                        type="select"
                                        size="lg"
                                        label="{{ __('Select Product/Service') }}"
                                    >
                                        <option value="">{{ __('Select Product') }}</option>
                                    </x-forms.input>
                                </div>
                            </div>
                        @endif

                        @foreach (json_decode($openai->questions) ?? [] as $question)
                            @php
                                $placeholder = isset($question->description) && !empty($question->description) ? __($question->description) : __($question->question);
                            @endphp
                            <x-forms.input
                                id="{{ $question->name }}"
                                size="lg"
                                containerClass="w-full"
                                label="{{ __($question->question) }}"
                                type="{{ $question->type === 'rss_feed' ? 'text' : $question->type }}"
                                name="{{ $question->name }}"
                                maxlength="{{ $setting->openai_max_input_length }}"
                                rows="{{ $question->type === 'textarea' ? 8 : null }}"
                                placeholder="{{ __($placeholder) }}"
                            >
                                @if ($question->type === 'select')
                                    @foreach ($question->selectListValues ?? [] as $input)
                                        <option value="{{ $input }}">
                                            {{ $input }}
                                        </option>
                                    @endforeach
                                @endif
                                @if ($question->type === 'rss_feed')
                                    <x-slot:action>
                                        <button
                                            class="fetch-rss flex h-full items-center gap-2 rounded-e-input px-3 text-2xs font-medium transition-colors hover:bg-secondary hover:text-secondary-foreground"
                                            type="button"
                                        >
                                            <x-tabler-refresh class="size-4" />
                                            {{ __('Fetch RSS') }}
                                        </button>
                                    </x-slot:action>
                                @endif
                            </x-forms.input>
                        @endforeach

                        @if ($openai->type == 'youtube')
                            <x-forms.input
                                id="youtube_action"
                                size="lg"
                                type="select"
                                label="{{ __('Action') }}"
                                containerClass="w-full"
                                name="youtube_action"
                                required
                            >
                                @foreach ($youtube_actions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-forms.input>
                        @endif

                        @if ($openai->type == 'text' || $openai->type == 'rss' || $openai->type == 'youtube')
                            <x-forms.input
                                id="language"
                                size="lg"
                                type="select"
                                label="{{ __('Language') }}"
                                containerClass="w-full"
                                name="language"
                                required
                            >
                                @include('panel.user.openai.components.countries')
                            </x-forms.input>
                        @endif

                        @if ($openai->type == 'text' || $openai->type == 'rss')
                            <x-forms.input
                                id="maximum_length"
                                size="lg"
                                type="number"
                                label="{{ __('Maximum Length') }}"
                                containerClass="w-full md:w-[48%]"
                                name="maximum_length"
                                max="{{ $setting->openai_max_output_length }}"
                                value="{{ $setting->openai_max_output_length }}"
                                placeholder="{{ __('Maximum character length of text') }}"
                                required
                            />

                            <x-forms.input
                                id="number_of_results"
                                size="lg"
                                type="number"
                                label="{{ __('Number of Results') }}"
                                containerClass="w-full md:w-[48%]"
                                name="number_of_results"
                                value="1"
                                placeholder="{{ __('Number of results') }}"
                                required
                            />

                            <x-forms.input
                                id="creativity"
                                size="lg"
                                type="select"
                                label="{{ __('Creativity') }}"
                                containerClass="w-full md:w-[48%]"
                                name="creativity"
                                required
                            >
                                @foreach ($creativity_levels as $creativity => $label)
                                    <option
                                        value="{{ $creativity }}"
                                        @selected($setting->openai_default_creativity == $creativity)
                                    >
                                        {{ __($label) }}
                                    </option>
                                @endforeach
                            </x-forms.input>

                            <x-forms.input
                                id="tone_of_voice"
                                size="lg"
                                type="select"
                                label="{{ __('Tone of Voice') }}"
                                containerClass="w-full md:w-[48%]"
                                name="tone_of_voice"
                                required
                            >
                                @foreach ($voice_tones as $tone)
                                    <option
                                        value="{{ $tone }}"
                                        @selected($setting->openai_default_tone_of_voice == $tone)
                                    >
                                        {{ __($tone) }}
                                    </option>
                                @endforeach
                            </x-forms.input>
                        @endif

                        <x-button
                            class="mt-3 w-full"
                            id="openai_generator_button"
                            tag="button"
                            type="submit"
                            size="lg"
                        >
                            <span class="hidden group-[.lqd-form-submitting]:inline-flex">{{ __('Please wait...') }}</span>
                            <span class="group-[.lqd-form-submitting]:hidden">{{ __('Generate') }}</span>
                        </x-button>

                    </form>
                </x-card>
            </div>

            <x-card
                id="workbook_textarea"
                @class([
                    'w-full [&_.tox-edit-area__iframe]:!bg-transparent',
                    'lg:border-s lg:ps-16' =>
                        Theme::getSetting('defaultVariations.card.variant', 'outline') ===
                        'outline',
                ])
                variant="{{ Theme::getSetting('defaultVariations.card.variant', 'outline') === 'outline' ? 'none' : Theme::getSetting('defaultVariations.card.variant', 'solid') }}"
                size="{{ Theme::getSetting('defaultVariations.card.variant', 'outline') === 'outline' ? 'none' : Theme::getSetting('defaultVariations.card.size', 'md') }}"
                roundness="{{ Theme::getSetting('defaultVariations.card.roundness', 'default') === 'default' ? 'none' : Theme::getSetting('defaultVariations.card.roundness', 'default') }}"
            >
                <div class="lqd-generator-actions flex w-full flex-wrap items-center gap-3 text-2xs">
                    <div class="flex grow">
                        @include('panel.user.openai.components.workbook-actions', [
                            'type' => $openai->type,
                            'title' => $openai->title,
                            'slug' => $openai->slug,
                            'output' => $openai->output,
                            'is_generated_doc' => true,
                        ])
                    </div>
                    <div
                        class="hidden justify-end"
                        id="savedDiv"
                    >
                        <a
                            class="text-nowrap flex items-center gap-1.5 rounded-md bg-green-600/10 px-2.5 py-1 text-[12px] font-medium leading-none text-green-700 transition-all hover:scale-105 hover:shadow-lg hover:shadow-green-600/5 dark:text-green-400"
                            href="{{ route('dashboard.user.openai.documents.all') }}"
                        >
                            <x-tabler-folder-check class="size-5" />
                            {{ __('Saved to') }}
                            <span class="underline">{{ __('Documents') }}</span>
                        </a>
                    </div>
                </div>
                <div class="lqd-generator-form-wrap mt-4 min-h-full w-full border-t pt-6">
                    @if ($openai->type == 'code')
                        <pre
                            class="line-numbers min-h-full [direction:ltr]"
                            id="code-pre"
                        ><code id="code-output">...</code></pre>
                    @else
                        <form class="workbook-form flex flex-col gap-4">
                            <x-forms.input
                                class="border-transparent px-0 font-serif text-2xl"
                                id="workbook_title"
                                placeholder="{{ __('Untitled Document...') }}"
                            />
                            <x-forms.input
                                class="tinymce border-0 font-body"
                                id="default"
                                type="textarea"
                                rows="25"
                            />
                        </form>
                    @endif
                </div>
            </x-card>
        </div>
    </div>

    <input
        id="guest_id"
        type="hidden"
        value="{{ $apiUrl }}"
    >
    <input
        id="guest_event_id"
        type="hidden"
        value="{{ $apikeyPart1 }}"
    >
    <input
        id="guest_look_id"
        type="hidden"
        value="{{ $apikeyPart2 }}"
    >
    <input
        id="guest_product_id"
        type="hidden"
        value="{{ $apikeyPart3 }}"
    >
@endsection

@push('script')
    <script>
        @if(setting('default_ai_engine') == 'anthropic')
            const stream_type = 'backend';
        @else
            const stream_type = '{!! $settings_two->openai_default_stream_server !!}';
        @endif

        const openai_model = '{{ $setting->openai_default_model }}';
        const default_ai_engine = '{{ setting('default_ai_engine', '$openai') }}'
    </script>
    <script src="{{ custom_theme_url('/assets/libs/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/js/panel/tinymce-theme-handler.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/js/panel/openai_generator_workbook.js') }}"></script>
    @if ($openai->type == 'code')
        <link
            rel="stylesheet"
            href="{{ custom_theme_url('/assets/libs/prism/prism.css') }}"
        >
        <script src="{{ custom_theme_url('/assets/libs/prism/prism.js') }}"></script>
    @endif

    <script>
        function sendOpenaiGeneratorForm(ev) {
            $('#savedDiv').addClass('hidden');

            tinyMCE?.activeEditor?.setContent('');

            ev?.preventDefault();
            ev?.stopPropagation();
            const submitBtn = document.getElementById("openai_generator_button");
            const editArea = document.querySelector('.tox-edit-area');
            const typingTemplate = document.querySelector('#typing-template').content.cloneNode(true);
            const typingEl = typingTemplate.firstElementChild;
            Alpine.store('appLoadingIndicator').show();
            submitBtn.classList.add('lqd-form-submitting');
            submitBtn.disabled = true;

            if (editArea) {
                if (!editArea.querySelector('.lqd-typing')) {
                    editArea.appendChild(typingEl);
                } else {
                    editArea.querySelector('.lqd-typing')?.classList?.remove('lqd-is-hidden');
                }
            }


            var formData = new FormData();
            // if brand checked then include company and product in request
            if (document.getElementById('brand')?.checked) {
                formData.append('company', document.getElementById('company').value);
                formData.append('product', document.getElementById('product').value);
            }
            formData.append('post_type', '{{ $openai->slug }}');
            formData.append('openai_id', '{{ $openai->id }}');

            @if ($openai->type == 'text')
                formData.append('maximum_length', $("#maximum_length").val());
                formData.append('number_of_results', $("#number_of_results").val());
                formData.append('creativity', $("#creativity").val());
                formData.append('tone_of_voice', $("#tone_of_voice").val());
                formData.append('language', $("#language").val());
            @endif

            @if ($openai->type == 'youtube')
                formData.append('language', $("#language").val());
                formData.append('youtube_action', $("#youtube_action").val());
            @endif

            @foreach (json_decode($openai->questions) ?? [] as $question)
                formData.append('{{ $question->name }}', $("{{ '#' . $question->name }}").val());
            @endforeach

            $.ajax({
                type: "post",
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                },
                url: "/dashboard/user/openai/generate",
                data: formData,
                contentType: false,
                processData: false,
                success: function(data) {
                    @if ($openai->type == 'code')
                        toastr.success("{{ __('Generated Successfully!') }}");
                        $("#workbook_textarea").html(data.html);
                        window.codeRaw = $("#code-output").text();
                        $("#code-output").addClass(`language-${$('#code_lang').val() || 'javascript'}`);
                        Prism.highlightElement($("#code-output")[0]);
                        submitBtn.classList.remove('lqd-form-submitting');
                        Alpine.store('appLoadingIndicator').hide();
                        document.querySelector('#workbook_regenerate')?.classList?.remove('hidden');
                        submitBtn.disabled = false;
                    @else
                        const typingEl = document.querySelector('.tox-edit-area > .lqd-typing');
                        const message_no = data.message_id;
                        const creativity = data.creativity;
                        const maximum_length = parseInt(data.maximum_length);
                        const number_of_results = data.number_of_results;
                        const prompt = data.inputPrompt;
                        return generate(message_no, creativity, maximum_length, number_of_results, prompt);
                    @endif
                    setTimeout(function() {
                        $('#savedDiv').removeClass('hidden');
                    }, 1000);
                },
                error: function(data) {
                    if (data.responseJSON.errors) {
                        $.each(data.responseJSON.errors, function(index, value) {
                            toastr.error(value);
                        });
                    } else if (data.responseJSON.message) {
                        toastr.error(data.responseJSON.message);
                    }
                    submitBtn.classList.remove('lqd-form-submitting');
                    Alpine.store('appLoadingIndicator').hide();
                    document.querySelector('#workbook_regenerate')?.classList?.add('hidden');
                    submitBtn.disabled = false;
                }
            });
            return false;
        }

        const deleteButton = document.getElementById("workbook_delete");
        deleteButton?.addEventListener("click", clearWorkbookContent);

        function clearWorkbookContent() {
            const editor = tinyMCE.activeEditor;
            if (editor) {
                editor.setContent("");
            }
        }
    </script>

    @if ($openai->type == 'rss')
        <script>
            $(document).on("click", ".fetch-rss", function(e) {
                "use strict";

                if (!$('#rss_feed').val()) {
                    toastr.error(@json(__('Enter the RSS URL!')));
                    return false;
                }

                var formData = new FormData();
                formData.append('url', $('#rss_feed').val());


                $.ajax({
                    type: "post",
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    },
                    url: "/rss/fetch",
                    data: formData,
                    contentType: false,
                    processData: false,
                    beforeSend: function() {
                        $('.fetch-rss svg').addClass('animate-spin');
                    },
                    success: function(data) {
                        $('.fetch-rss svg').removeClass('animate-spin');
                        $('#title').empty();
                        $('#title').append(data);
                        toastr.success(@json(__('RSS Fetched Successifuly!')));
                    },
                    error: function(data) {
                        $('.fetch-rss svg').removeClass('animate-spin');
                        toastr.error(data.responseJSON);
                    }
                });

            });
        </script>
    @endif

    <script>
        $('#company').on('change', function() {
            var brand_id = $(this).val();
            if (brand_id == '') {
                $('#product').empty();
                $('#product').append('<option value="">Select Product</option>');
                $('#tone_of_voice option').prop('selected', false);
            } else {
                $.ajax({
                    url: '/dashboard/user/brand/get-products/' + brand_id,
                    type: 'get',
                    success: function(response) {
                        $('#product').empty();
                        if (response.length == 0) {
                            $('#product').append('<option value="">Select Product</option>');
                        } else {
                            $.each(response, function(index, value) {
                                $('#product').append('<option value="' + value.id + '">' + value
                                    .name +
                                    '</option>');
                            });
                        }
                        $('#tone_of_voice').val($('#company :selected').attr('data-tone_of_voice'));
                    }
                });
            }

        });
    </script>

@endpush
