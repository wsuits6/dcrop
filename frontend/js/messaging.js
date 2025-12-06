/**
 * DCROP System - Messaging JavaScript
 * File: frontend/js/messaging.js
 * Functions for messaging between users
 */

/**
 * Send message from student to coordinator
 * @param {number} studentId
 * @param {number} coordinatorId
 * @param {string} message
 * @returns {Promise<object>}
 */
async function sendMessageToCoordinator(studentId, coordinatorId, message) {
    try {
        const response = await fetch(
            `${DCROP.API_BASE_URL}/api/messages.php?action=send_to_coordinator`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    student_id: studentId,
                    coordinator_id: coordinatorId,
                    message: message
                })
            }
        );

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Send message error:', error);
        throw error;
    }
}

/**
 * Escalate message from coordinator to admin
 * @param {number} coordinatorId
 * @param {number} adminId
 * @param {string} message
 * @returns {Promise<object>}
 */
async function escalateMessageToAdmin(coordinatorId, adminId, message) {
    try {
        const response = await fetch(
            `${DCROP.API_BASE_URL}/api/messages.php?action=escalate`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    coordinator_id: coordinatorId,
                    admin_id: adminId,
                    message: message
                })
            }
        );

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Escalate message error:', error);
        throw error;
    }
}

/**
 * Send generic message (any role to any role)
 * @param {number} senderId
 * @param {number} receiverId
 * @param {string} senderRole
 * @param {string} receiverRole
 * @param {string} message
 * @returns {Promise<object>}
 */
async function sendMessage(senderId, receiverId, senderRole, receiverRole, message) {
    try {
        const response = await fetch(
            `${DCROP.API_BASE_URL}/api/messages.php?action=send`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    sender_id: senderId,
                    receiver_id: receiverId,
                    sender_role: senderRole,
                    receiver_role: receiverRole,
                    message: message
                })
            }
        );

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Send message error:', error);
        throw error;
    }
}

/**
 * Get received messages
 * @param {number} userId
 * @param {string} userRole
 * @returns {Promise<array>}
 */
async function getReceivedMessages(userId, userRole) {
    try {
        const response = await fetch(
            `${DCROP.API_BASE_URL}/api/messages.php?action=received&user_id=${userId}&user_role=${userRole}`
        );

        const result = await response.json();
        
        if (result.success) {
            return result.messages;
        } else {
            throw new Error(result.message || 'Failed to load messages');
        }
    } catch (error) {
        console.error('Get received messages error:', error);
        throw error;
    }
}

/**
 * Get sent messages
 * @param {number} userId
 * @param {string} userRole
 * @returns {Promise<array>}
 */
async function getSentMessages(userId, userRole) {
    try {
        const response = await fetch(
            `${DCROP.API_BASE_URL}/api/messages.php?action=sent&user_id=${userId}&user_role=${userRole}`
        );

        const result = await response.json();
        
        if (result.success) {
            return result.messages;
        } else {
            throw new Error(result.message || 'Failed to load sent messages');
        }
    } catch (error) {
        console.error('Get sent messages error:', error);
        throw error;
    }
}

/**
 * Get unread message count
 * @param {number} userId
 * @param {string} userRole
 * @returns {Promise<number>}
 */
async function getUnreadMessageCount(userId, userRole) {
    try {
        const response = await fetch(
            `${DCROP.API_BASE_URL}/api/messages.php?action=unread_count&user_id=${userId}&user_role=${userRole}`
        );

        const result = await response.json();
        
        if (result.success) {
            return result.unread_count;
        } else {
            return 0;
        }
    } catch (error) {
        console.error('Get unread count error:', error);
        return 0;
    }
}

/**
 * Mark message as read
 * @param {number} messageId
 * @returns {Promise<object>}
 */
async function markMessageAsRead(messageId) {
    try {
        const response = await fetch(
            `${DCROP.API_BASE_URL}/api/messages.php?action=mark_read`,
            {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message_id: messageId
                })
            }
        );

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Mark as read error:', error);
        throw error;
    }
}

/**
 * Mark all messages as read
 * @param {number} userId
 * @param {string} userRole
 * @returns {Promise<object>}
 */
async function markAllMessagesAsRead(userId, userRole) {
    try {
        const response = await fetch(
            `${DCROP.API_BASE_URL}/api/messages.php?action=mark_all_read`,
            {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: userId,
                    user_role: userRole
                })
            }
        );

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Mark all as read error:', error);
        throw error;
    }
}

/**
 * Delete message
 * @param {number} messageId
 * @returns {Promise<object>}
 */
async function deleteMessage(messageId) {
    try {
        const response = await fetch(
            `${DCROP.API_BASE_URL}/api/messages.php?action=delete&message_id=${messageId}`,
            {
                method: 'DELETE'
            }
        );

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Delete message error:', error);
        throw error;
    }
}

/**
 * Filter messages by read status
 * @param {array} messages
 * @param {boolean} isRead - true for read, false for unread
 * @returns {array}
 */
function filterMessagesByReadStatus(messages, isRead) {
    return messages.filter(msg => {
        return isRead ? msg.is_read == 1 : msg.is_read == 0;
    });
}

/**
 * Filter messages by sender role
 * @param {array} messages
 * @param {string} senderRole
 * @returns {array}
 */
function filterMessagesBySenderRole(messages, senderRole) {
    if (!senderRole) return messages;
    return messages.filter(msg => msg.sender_role === senderRole);
}

/**
 * Filter messages by date range
 * @param {array} messages
 * @param {string} startDate
 * @param {string} endDate
 * @returns {array}
 */
function filterMessagesByDateRange(messages, startDate, endDate) {
    if (!startDate && !endDate) return messages;

    return messages.filter(msg => {
        const msgDate = new Date(msg.created_at);
        const start = startDate ? new Date(startDate) : new Date('1900-01-01');
        const end = endDate ? new Date(endDate) : new Date('2100-12-31');

        return msgDate >= start && msgDate <= end;
    });
}

/**
 * Search messages by content
 * @param {array} messages
 * @param {string} searchTerm
 * @returns {array}
 */
function searchMessages(messages, searchTerm) {
    if (!searchTerm) return messages;

    const term = searchTerm.toLowerCase();

    return messages.filter(msg => {
        return (
            msg.message.toLowerCase().includes(term) ||
            (msg.sender_name && msg.sender_name.toLowerCase().includes(term)) ||
            (msg.receiver_name && msg.receiver_name.toLowerCase().includes(term))
        );
    });
}

/**
 * Sort messages by date
 * @param {array} messages
 * @param {string} order - 'asc' or 'desc'
 * @returns {array}
 */
function sortMessagesByDate(messages, order = 'desc') {
    return [...messages].sort((a, b) => {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);

        return order === 'asc' ? dateA - dateB : dateB - dateA;
    });
}

/**
 * Group messages by date
 * @param {array} messages
 * @returns {object}
 */
function groupMessagesByDate(messages) {
    const grouped = {};

    messages.forEach(msg => {
        const date = msg.created_at.split(' ')[0]; // Get date part only

        if (!grouped[date]) {
            grouped[date] = [];
        }

        grouped[date].push(msg);
    });

    return grouped;
}

/**
 * Get message statistics
 * @param {array} messages
 * @returns {object}
 */
function getMessageStatistics(messages) {
    const total = messages.length;
    const unread = messages.filter(msg => msg.is_read == 0).length;
    const read = messages.filter(msg => msg.is_read == 1).length;

    // Group by sender role
    const byRole = {
        student: messages.filter(msg => msg.sender_role === 'student').length,
        coordinator: messages.filter(msg => msg.sender_role === 'coordinator').length,
        admin: messages.filter(msg => msg.sender_role === 'admin').length
    };

    return {
        total,
        unread,
        read,
        byRole
    };
}

/**
 * Validate message content
 * @param {string} message
 * @param {number} maxLength
 * @returns {object} {valid: boolean, message: string}
 */
function validateMessageContent(message, maxLength = 1000) {
    if (!message || message.trim().length === 0) {
        return {
            valid: false,
            message: 'Message cannot be empty'
        };
    }

    if (message.length > maxLength) {
        return {
            valid: false,
            message: `Message is too long (max ${maxLength} characters)`
        };
    }

    return {
        valid: true,
        message: 'Message is valid'
    };
}

/**
 * Format message for display
 * @param {object} messageObj
 * @returns {string} HTML string
 */
function formatMessageCard(messageObj) {
    const timeAgo = DCROP.getTimeAgo(messageObj.created_at);
    const readStatus = messageObj.is_read ? 'read' : 'unread';
    const readBadge = messageObj.is_read ? '✓ Read' : '• Unread';

    return `
        <div class="message-card ${readStatus}" data-message-id="${messageObj.id}">
            <div class="message-header">
                <div class="sender-info">
                    <span class="sender-icon">👤</span>
                    <div class="sender-details">
                        <h4 class="sender-name">${messageObj.sender_name || 'Unknown'}</h4>
                        <p class="sender-role">${messageObj.sender_role}</p>
                    </div>
                </div>
                <div class="message-meta">
                    <span class="message-time">${timeAgo}</span>
                    <span class="message-status status-${readStatus}">${readBadge}</span>
                </div>
            </div>
            <div class="message-body">
                <p class="message-text">${escapeHTML(messageObj.message)}</p>
            </div>
            <div class="message-actions">
                ${!messageObj.is_read ? 
                    `<button class="btn-sm btn-primary" onclick="markAsRead(${messageObj.id})">✓ Mark as Read</button>` : 
                    ''}
                <button class="btn-sm btn-danger" onclick="deleteMessage(${messageObj.id})">🗑️ Delete</button>
            </div>
        </div>
    `;
}

/**
 * Escape HTML to prevent XSS
 * @param {string} text
 * @returns {string}
 */
function escapeHTML(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Get recipient name based on role
 * @param {string} role
 * @returns {string}
 */
function getRecipientDisplayName(role) {
    const names = {
        'student': 'Student',
        'coordinator': 'Coordinator',
        'admin': 'Administrator',
        'super_user': 'Super User'
    };
    return names[role] || 'User';
}

/**
 * Get role icon
 * @param {string} role
 * @returns {string}
 */
function getRoleIcon(role) {
    const icons = {
        'student': '👨‍🎓',
        'coordinator': '👨‍💼',
        'admin': '👑',
        'super_user': '⚡'
    };
    return icons[role] || '👤';
}

/**
 * Check if user can send message to role
 * @param {string} senderRole
 * @param {string} receiverRole
 * @returns {boolean}
 */
function canSendMessageToRole(senderRole, receiverRole) {
    // Define allowed messaging paths
    const allowedPaths = {
        'student': ['coordinator'],
        'coordinator': ['admin', 'super_user'],
        'admin': ['coordinator', 'super_user'],
        'super_user': ['admin', 'coordinator', 'student']
    };

    return allowedPaths[senderRole]?.includes(receiverRole) || false;
}

/**
 * Get last message time
 * @param {array} messages
 * @returns {string|null}
 */
function getLastMessageTime(messages) {
    if (messages.length === 0) return null;

    const sorted = sortMessagesByDate(messages, 'desc');
    return sorted[0].created_at;
}

/**
 * Count messages by sender
 * @param {array} messages
 * @param {number} senderId
 * @returns {number}
 */
function countMessagesBySender(messages, senderId) {
    return messages.filter(msg => msg.sender_id == senderId).length;
}

/**
 * Export messages to CSV
 * @param {array} messages
 * @param {string} filename
 */
function exportMessagesToCSV(messages, filename = 'messages') {
    const csvData = messages.map(msg => ({
        'Date': DCROP.formatDateTime(msg.created_at),
        'From': msg.sender_name || 'Unknown',
        'Role': msg.sender_role,
        'Message': msg.message,
        'Status': msg.is_read ? 'Read' : 'Unread'
    }));

    DCROP.downloadCSV(csvData, filename);
}

/**
 * Get messages for today
 * @param {array} messages
 * @returns {array}
 */
function getTodayMessages(messages) {
    const today = new Date().toISOString().split('T')[0];
    
    return messages.filter(msg => {
        const msgDate = msg.created_at.split(' ')[0];
        return msgDate === today;
    });
}

/**
 * Auto-refresh messages
 * @param {function} callback - Function to call on refresh
 * @param {number} interval - Interval in milliseconds
 * @returns {number} Interval ID
 */
function startMessageAutoRefresh(callback, interval = 30000) {
    return setInterval(callback, interval);
}

/**
 * Stop auto-refresh messages
 * @param {number} intervalId
 */
function stopMessageAutoRefresh(intervalId) {
    clearInterval(intervalId);
}

/**
 * Update unread badge
 * @param {number} count
 */
function updateUnreadBadge(count) {
    const badges = document.querySelectorAll('#messageBadge, .message-badge');
    badges.forEach(badge => {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    });
}

// Export to global DCROP namespace
if (window.DCROP) {
    window.DCROP.Messaging = {
        sendMessageToCoordinator,
        escalateMessageToAdmin,
        sendMessage,
        getReceivedMessages,
        getSentMessages,
        getUnreadMessageCount,
        markMessageAsRead,
        markAllMessagesAsRead,
        deleteMessage,
        filterMessagesByReadStatus,
        filterMessagesBySenderRole,
        filterMessagesByDateRange,
        searchMessages,
        sortMessagesByDate,
        groupMessagesByDate,
        getMessageStatistics,
        validateMessageContent,
        formatMessageCard,
        escapeHTML,
        getRecipientDisplayName,
        getRoleIcon,
        canSendMessageToRole,
        getLastMessageTime,
        countMessagesBySender,
        exportMessagesToCSV,
        getTodayMessages,
        startMessageAutoRefresh,
        stopMessageAutoRefresh,
        updateUnreadBadge
    };
}