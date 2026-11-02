<?php

/**
 * TinyMCE editor on the textarea of the content forms
 *
 * @var \App\View\AppView $this
 */

$this->prepend('script', $this->Html->script(['/js/tinymce/tinymce.min.js']));
?>
<script>
  tinymce.init({
    selector: 'textarea',
    plugins: 'preview importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap emoticons',
    menubar: 'edit view insert format table help',
    toolbar: 'bold italic underline | fontfamily fontsize blocks | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | insertfile image media template link',
    toolbar_mode: 'wrap',
    height: 400,
    license_key: 'gpl',
    promotion: false,
    branding: false
  });
</script>
