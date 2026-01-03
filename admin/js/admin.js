/**
 * Akanyenyeri Admin Dashboard JavaScript
 * Core functionality for the admin panel
 */

// ==================== AUTHENTICATION ====================

/**
 * Check if user is authenticated
 */
function checkAuth() {
    if (localStorage.getItem('adminLoggedIn') !== 'true') {
        window.location.href = 'index.html';
        return false;
    }
    
    // Set admin name
    const adminName = localStorage.getItem('adminUser') || 'Admin';
    const nameElements = document.querySelectorAll('#adminName, #welcomeName');
    nameElements.forEach(el => {
        if (el) el.textContent = adminName;
    });
    
    return true;
}

/**
 * Logout user
 */
function logout() {
    localStorage.removeItem('adminLoggedIn');
    localStorage.removeItem('adminUser');
    window.location.href = 'index.html';
}

// ==================== UI FUNCTIONS ====================

/**
 * Toggle sidebar on mobile
 */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('open');
}

/**
 * Toggle user dropdown menu
 */
function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('userDropdown');
    const userMenu = document.querySelector('.topbar-user');
    
    if (dropdown && userMenu && !userMenu.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

/**
 * Toggle submenu in sidebar
 */
document.addEventListener('DOMContentLoaded', function() {
    const submenus = document.querySelectorAll('.has-submenu > a');
    submenus.forEach(function(menu) {
        menu.addEventListener('click', function(e) {
            e.preventDefault();
            this.parentElement.classList.toggle('open');
        });
    });
});

// ==================== POST MANAGEMENT ====================

/**
 * Get all posts from localStorage
 */
function getPosts() {
    const posts = localStorage.getItem('akanyenyeri_posts');
    if (posts) {
        return JSON.parse(posts);
    }
    
    // Return sample posts if none exist
    const samplePosts = [
        {
            id: 1,
            title: 'The Future of Artificial Intelligence',
            slug: 'future-of-artificial-intelligence',
            content: '<p>Artificial Intelligence is transforming our world in unprecedented ways...</p>',
            excerpt: 'Discover how AI is changing the way we live and work.',
            category: 'Technology',
            tags: ['AI', 'Technology', 'Future'],
            status: 'published',
            visibility: 'public',
            date: '2024-12-18',
            author: 'Admin',
            featuredImage: null
        },
        {
            id: 2,
            title: 'Global Markets Show Strong Recovery',
            slug: 'global-markets-recovery',
            content: '<p>Economic indicators point to sustained growth...</p>',
            excerpt: 'Markets respond positively to recent developments.',
            category: 'Business',
            tags: ['Business', 'Economy', 'Markets'],
            status: 'published',
            visibility: 'public',
            date: '2024-12-17',
            author: 'Admin',
            featuredImage: null
        },
        {
            id: 3,
            title: 'Championship Finals Draw Record Crowds',
            slug: 'championship-finals-record-crowds',
            content: '<p>Sports fans gather in record numbers for the finals...</p>',
            excerpt: 'Historic attendance at this year\'s championship.',
            category: 'Sports',
            tags: ['Sports', 'Championship', 'Football'],
            status: 'published',
            visibility: 'public',
            date: '2024-12-16',
            author: 'Admin',
            featuredImage: null
        },
        {
            id: 4,
            title: 'New Medical Breakthrough Announced',
            slug: 'medical-breakthrough-announced',
            content: '<p>Scientists have made a significant discovery...</p>',
            excerpt: 'Revolutionary treatment shows promising results.',
            category: 'Health',
            tags: ['Health', 'Medical', 'Science'],
            status: 'draft',
            visibility: 'public',
            date: '2024-12-15',
            author: 'Admin',
            featuredImage: null
        },
        {
            id: 5,
            title: 'Entertainment Industry Trends for 2025',
            slug: 'entertainment-trends-2025',
            content: '<p>What to expect in the entertainment world next year...</p>',
            excerpt: 'A look at upcoming trends in movies, music, and more.',
            category: 'Entertainment',
            tags: ['Entertainment', 'Trends', '2025'],
            status: 'pending',
            visibility: 'public',
            date: '2024-12-14',
            author: 'Editor',
            featuredImage: null
        }
    ];
    
    localStorage.setItem('akanyenyeri_posts', JSON.stringify(samplePosts));
    return samplePosts;
}

/**
 * Save a new post
 */
function savePost(post) {
    let posts = getPosts();
    posts.unshift(post); // Add to beginning
    localStorage.setItem('akanyenyeri_posts', JSON.stringify(posts));
    return post;
}

/**
 * Update an existing post
 */
function updatePost(id, updatedData) {
    let posts = getPosts();
    const index = posts.findIndex(p => p.id === id);
    
    if (index !== -1) {
        posts[index] = { ...posts[index], ...updatedData };
        localStorage.setItem('akanyenyeri_posts', JSON.stringify(posts));
        return posts[index];
    }
    
    return null;
}

/**
 * Delete a post
 */
function deletePost(id) {
    let posts = getPosts();
    posts = posts.filter(p => p.id !== id);
    localStorage.setItem('akanyenyeri_posts', JSON.stringify(posts));
}

/**
 * Get a single post by ID
 */
function getPost(id) {
    const posts = getPosts();
    return posts.find(p => p.id === id);
}

// ==================== CATEGORY MANAGEMENT ====================

/**
 * Get all categories
 */
function getCategories() {
    const defaultCategories = [
        { id: 1, name: 'Technology', slug: 'technology', count: 0 },
        { id: 2, name: 'Business', slug: 'business', count: 0 },
        { id: 3, name: 'Sports', slug: 'sports', count: 0 },
        { id: 4, name: 'Entertainment', slug: 'entertainment', count: 0 },
        { id: 5, name: 'Health', slug: 'health', count: 0 },
        { id: 6, name: 'Lifestyle', slug: 'lifestyle', count: 0 },
        { id: 7, name: 'Politics', slug: 'politics', count: 0 },
        { id: 8, name: 'Opinion', slug: 'opinion', count: 0 },
        { id: 9, name: 'Uncategorized', slug: 'uncategorized', count: 0 }
    ];
    
    const categories = localStorage.getItem('akanyenyeri_categories');
    if (categories) {
        return JSON.parse(categories);
    }
    
    localStorage.setItem('akanyenyeri_categories', JSON.stringify(defaultCategories));
    return defaultCategories;
}

// ==================== MEDIA MANAGEMENT ====================

/**
 * Get all media items
 */
function getMedia() {
    const media = localStorage.getItem('akanyenyeri_media');
    return media ? JSON.parse(media) : [];
}

/**
 * Save media item
 */
function saveMedia(item) {
    let media = getMedia();
    media.unshift(item);
    localStorage.setItem('akanyenyeri_media', JSON.stringify(media));
    return item;
}

// ==================== UTILITY FUNCTIONS ====================

/**
 * Generate slug from string
 */
function generateSlug(str) {
    return str.toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

/**
 * Format date
 */
function formatDate(dateStr) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateStr).toLocaleDateString('en-US', options);
}

/**
 * Truncate text
 */
function truncateText(text, length = 100) {
    if (text.length <= length) return text;
    return text.substring(0, length) + '...';
}

/**
 * Strip HTML tags
 */
function stripHtml(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
}

// ==================== DASHBOARD STATS ====================

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    const posts = getPosts();
    
    return {
        totalPosts: posts.length,
        published: posts.filter(p => p.status === 'published').length,
        drafts: posts.filter(p => p.status === 'draft').length,
        pending: posts.filter(p => p.status === 'pending').length,
        categories: getCategories().length
    };
}

// ==================== NOTIFICATIONS ====================

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fa fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ==================== SEARCH FUNCTIONALITY ====================

/**
 * Search posts
 */
function searchPosts(query) {
    const posts = getPosts();
    const lowercaseQuery = query.toLowerCase();
    
    return posts.filter(post => 
        post.title.toLowerCase().includes(lowercaseQuery) ||
        post.content.toLowerCase().includes(lowercaseQuery) ||
        post.category.toLowerCase().includes(lowercaseQuery) ||
        (post.tags && post.tags.some(tag => tag.toLowerCase().includes(lowercaseQuery)))
    );
}

// ==================== EXPORT DATA ====================

/**
 * Export posts as JSON
 */
function exportPosts() {
    const posts = getPosts();
    const dataStr = JSON.stringify(posts, null, 2);
    const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);
    
    const exportName = 'akanyenyeri-posts-' + new Date().toISOString().split('T')[0] + '.json';
    
    const linkElement = document.createElement('a');
    linkElement.setAttribute('href', dataUri);
    linkElement.setAttribute('download', exportName);
    linkElement.click();
}

/**
 * Import posts from JSON
 */
function importPosts(jsonData) {
    try {
        const posts = JSON.parse(jsonData);
        if (Array.isArray(posts)) {
            localStorage.setItem('akanyenyeri_posts', JSON.stringify(posts));
            return true;
        }
    } catch (e) {
        console.error('Import error:', e);
    }
    return false;
}

console.log('Akanyenyeri Admin JS loaded successfully');

