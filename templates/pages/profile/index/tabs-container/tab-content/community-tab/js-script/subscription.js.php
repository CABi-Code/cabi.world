// Подписка/отписка
async function toggleSubscription(communityId, subscribe) {
    const endpoint = subscribe ? '/api/community/subscribe' : '/api/community/unsubscribe';
    const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ community_id: communityId })
    });
    
    if (res.ok) {
        location.reload();
    }
}
