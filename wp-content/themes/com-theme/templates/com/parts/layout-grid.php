<div class="pointer-events-none fixed inset-0 z-[5000] opacity-0 transition-opacity group-[.show-layout-grid]:opacity-50" data-module-layout-grid aria-hidden="true">
    <div class="h-full w-full">
        <div class="grid h-full grid-cols-12 border-r border-[#00fbff] md:hidden">
            <?php for ($column = 0; $column < 12; $column++) : ?>
                <div class="border-l border-[#00fbff]"></div>
            <?php endfor; ?>
        </div>
        <div class="hidden h-full grid-cols-24 border-r border-[#00fbff] md:grid">
            <?php for ($column = 0; $column < 24; $column++) : ?>
                <div class="border-l border-[#00fbff]"></div>
            <?php endfor; ?>
        </div>
    </div>
</div>
