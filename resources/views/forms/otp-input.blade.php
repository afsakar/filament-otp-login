@php
    $extraAlpineAttributes = $getExtraAlpineAttributes();
    $id = $getId();
    $isConcealed = $isConcealed();
    $isDisabled = $isDisabled();
    $statePath = $getStatePath();
    $hintActions = $getHintActions();
    $numberLength = $getNumberLength();
    $isAutofocused = $isAutofocused();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :id="$getId()"
    :label="$getLabel()"
    :label-sr-only="$isLabelHidden()"
    :helper-text="$getHelperText()"
    :hint="$getHint()"
    :hint-icon="$getHintIcon()"
    :required="$isRequired()"
    :state-path="$getStatePath()"
>
    <div x-data="{
    	    state: '{{ $getStatePath() }}',
    	    length: {{$numberLength}},
    	    autoFocus: '{{$isAutofocused}}',
    	    type: 'text',
            init: function(){
                if (this.autoFocus){
                    this.$refs[1].focus();
                }
            },
            handleInput(e, i) {
                const input = e.target;

                if(input.value.length > 1){
                    input.value = input.value.substring(0, 1);
                }

                this.state = Array.from(Array(this.length), (element, i) => {
                    const el = this.$refs[(i + 1)];
                    return el.value ? el.value : '';
                }).join('');


                if (i < this.length) {
                    this.$refs[i+1].focus();
                    this.$refs[i+1].select();
                }

                if(i == this.length){
                    @this.set('{{ $getStatePath() }}', this.state)
                }
            },

            handlePaste(e) {
                const paste = e.clipboardData.getData('text');
                this.value = paste;
                const inputs = Array.from(Array(this.length));

                inputs.forEach((element, i) => {
                    this.$refs[(i+1)].focus();
                    this.$refs[(i+1)].value = paste[i] || '';
                });
            },

            handleBackspace(e) {
                const ref = e.target.getAttribute('x-ref');
                e.target.value = '';
                const previous = ref - 1;
                this.$refs[previous] && this.$refs[previous].focus();
                this.$refs[previous] && this.$refs[previous].select();
                e.preventDefault();
            },
        }">
        <div class="flex justify-between gap-6 pt-3 pb-2 h-16">

            @foreach(range(1, $numberLength) as $column)
                <x-filament::input.wrapper
                    :disabled="$isDisabled"
                    :valid="! $errors->has($statePath)"
                    :attributes="
                        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
                        ->class(['fi-fo-text-input overflow-hidden'])
                    "
                >
                    <input
                        {{$isDisabled ? 'disabled' : ''}}
                        type="text"
                        maxlength="1"
                        x-ref="{{$column}}"
                        required
                        class="fi-input block w-full border-none text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500 sm:leading-6 bg-white/0 text-center"
                        x-on:input="handleInput($event, {{$column}})"
                        x-on:paste="handlePaste($event)"
                        x-on:keydown.backspace="handleBackspace($event)"
                    />

                </x-filament::input.wrapper>
            @endforeach

        </div>
    </div>
</x-dynamic-component>
