define([], function() {
    function apply(action, status) {
        action.classList.remove('btn-primary', 'btn-secondary', 'btn-success', 'btn-warning', 'disabled');
        action.style.borderColor = '';
        action.style.backgroundColor = '';
        action.style.color = '';
        action.removeAttribute('aria-disabled');
        if (status.enrolled) {
            action.classList.add('btn', 'disabled');
            action.style.backgroundColor = '#dff3e4';
            action.style.borderColor = '#9fd3ad';
            action.style.color = '#1f6b35';
            action.setAttribute('aria-disabled', 'true');
            action.removeAttribute('href');
        } else if (status.closed) {
            action.classList.add('btn', 'disabled');
            action.style.backgroundColor = '#fff0d5';
            action.style.borderColor = '#efbd68';
            action.style.color = '#8a4b00';
            action.setAttribute('aria-disabled', 'true');
            action.removeAttribute('href');
        } else {
            action.classList.add('btn', 'btn-primary');
        }
    }

    function update(courseid) {
        var cards = document.querySelectorAll('.local-ga-card-actions[data-editionid]');
        if (!cards.length) {
            return;
        }
        var root = (window.M && M.cfg && M.cfg.wwwroot) ? M.cfg.wwwroot : '';
        fetch(root + '/local/gestion_actividades/card_status.php?courseid=' + encodeURIComponent(courseid), {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {'Accept': 'application/json'}
        }).then(function(response) {
            return response.ok ? response.json() : null;
        }).then(function(data) {
            if (!data || !data.statuses) {
                return;
            }
            cards.forEach(function(card) {
                var status = data.statuses[card.getAttribute('data-editionid')];
                var action = card.querySelector('.local-ga-enrol-status');
                if (status && action) {
                    action.textContent = status.label;
                    apply(action, status);
                }
            });
        }).catch(function() {});
    }

    return {
        init: function(courseid) {
            var run = function() { update(courseid); };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', run, {once: true});
            } else {
                run();
            }
        }
    };
});
