<div class="prose max-w-full font-light
            text-14
            prose-h2:text-inherit prose-h2:text-14 prose-h2:font-bold prose-h2:text-white prose-h2:m-0 prose-h2:leading-none
            prose-h3:text-inherit prose-h3:text-14 prose-h3:font-bold prose-h3:text-white prose-h3:m-0 prose-h3:leading-none
            prose-h4:text-inherit prose-h4:text-14 prose-h4:font-bold prose-h4:text-white prose-h4:m-0 prose-h4:leading-none
            prose-h5:text-inherit prose-h5:text-14 prose-h5:font-bold prose-h5:text-white prose-h5:m-0 prose-h5:leading-none
            prose-h6:text-inherit prose-h6:text-14 prose-h6:font-bold prose-h6:text-white prose-h6:m-0 prose-h6:leading-none
            prose-p:text-14 prose-p:leading-none
            hidden group-[.form.error]:block group-[.form.success]:block"
     data-form="messages"
>

    <div class="hidden group-[.form.success]:block space-y-10">
        <?php the_field( 'success_message', get_field( 'form' ) ) ?>
    </div>

    <div class="text-error hidden group-[.form.error]:block space-y-10">
        <?php the_field( 'error_message', get_field( 'form' ) ) ?>
        <div class="mt-10 text-12" data-form="error-message"></div>
    </div>

</div>

