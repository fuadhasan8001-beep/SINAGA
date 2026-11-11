const form = document.querySelector('[data-spmb-editor]');

if (form) {
    const posterInput = form.querySelector('[data-poster-input]');
    const posterPreview = form.querySelector('[data-poster-preview]');
    const currentPoster = form.querySelector('[data-current-poster]');
    let selectedPosterUrl = null;

    posterInput?.addEventListener('change', () => {
        const file = posterInput.files?.[0];
        if (selectedPosterUrl) URL.revokeObjectURL(selectedPosterUrl);
        selectedPosterUrl = file ? URL.createObjectURL(file) : null;

        if (posterPreview) {
            posterPreview.hidden = !file;
            const previewImage = posterPreview.querySelector('img');
            if (previewImage && selectedPosterUrl) previewImage.src = selectedPosterUrl;
        }
    });
}
