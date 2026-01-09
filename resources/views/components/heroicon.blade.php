{{--
    Reusable HeroIcon Component - Professional Icon System

    Usage:
    <x-heroicon name="home" />                          <!-- Default: outline, 20px -->
    <x-heroicon name="plus" class="w-6 h-6" />          <!-- Custom size -->
    <x-heroicon name="trash" class="w-5 h-5 text-red-500" /> <!-- With color -->
    <x-heroicon name="check-circle" variant="solid" />  <!-- Solid variant -->

    Available Icons: home, users, lock-closed, wrench-screwdriver, arrow-right-on-rectangle,
    plus, trash, pencil-square, eye, download, check-circle, exclamation-circle,
    arrow-down-circle, arrow-up-circle, building-office-2, shopping-bag, plane,
    ticket, shopping-bag, wrench, document-text, credit-card, and more...

    See: https://heroicons.com/
--}}

@props([
    'name' => '',
    'variant' => 'outline',
])

@if($name)
    @php
        // Determine the variant prefix (o=outline, s=solid, c=compact, m=mini)
        $variantPrefix = match($variant) {
            'solid' => 's',
            'compact' => 'c',
            'mini' => 'm',
            default => 'o', // outline is default
        };

        // blade-heroicons uses the "heroicon" prefix
        // Icons are referenced as: heroicon-o-arrow-up-circle, heroicon-s-star, etc.
        $kebabName = Str::kebab($name);
        $iconName = 'heroicon-' . $variantPrefix . '-' . $kebabName;

        $classes = $attributes->get('class', 'w-5 h-5');
        $iconAttributes = array_merge(['class' => $classes], $attributes->except('class')->getAttributes());
    @endphp

    {{-- Dynamically render the icon component from blade-icons --}}
    <x-dynamic-component :component="'icon'" :name="$iconName" :attributes="$iconAttributes" />
@endif
