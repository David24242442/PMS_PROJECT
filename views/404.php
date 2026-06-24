<?php
/**
 * 404 Error Page
 */
?>

<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 60vh; text-align: center;">
    <span class="material-symbols-sharp" style="font-size: 6rem; color: var(--color-primary); margin-bottom: 1rem;">
        search_off
    </span>
    <h1 style="font-size: 3rem; color: var(--text-primary); margin-bottom: 0.5rem;">404</h1>
    <h2 style="color: var(--text-secondary); margin-bottom: 1rem;">Page Not Found</h2>
    <p style="color: var(--text-muted); max-width: 400px; margin-bottom: 2rem;">
        The page you're looking for doesn't exist or has been moved. 
        Please check the URL or navigate using the sidebar.
    </p>
    <div class="flex gap-md">
        <a href="index.php?page=pms-dashboard" class="btn btn-primary">
            <span class="material-symbols-sharp">home</span>
            Go to Dashboard
        </a>
        <button class="btn btn-outline" onclick="history.back()">
            <span class="material-symbols-sharp">arrow_back</span>
            Go Back
        </button>
    </div>
</div>
