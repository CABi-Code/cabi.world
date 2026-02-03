/**
 * API модуль для загрузки модпаков
 */

export async function loadModpacks(sortBy = 'downloads') {
    const response = await fetch(`/api/modpack-selector/list?sort=${sortBy}`);
    if (!response.ok) throw new Error('Failed to load modpacks');
    
    const data = await response.json();
    return data.modpacks || [];
}

export async function searchModpacks(query, sortBy = 'downloads') {
    const params = new URLSearchParams({ q: query, sort: sortBy });
    const response = await fetch(`/api/modpack-selector/search?${params}`);
    if (!response.ok) throw new Error('Failed to search modpacks');
    
    const data = await response.json();
    return data.modpacks || [];
}
