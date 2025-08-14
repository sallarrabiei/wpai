(function(){
	function $(sel, ctx){ return (ctx||document).querySelector(sel); }
	function el(tag, cls){ const e=document.createElement(tag); if(cls) e.className=cls; return e; }

	document.addEventListener('DOMContentLoaded', function(){
		var containers = document.querySelectorAll('[data-raswpai-chat]');
		containers.forEach(function(container){
			var messagesEl = container.querySelector('.raswpai-messages');
			var form = container.querySelector('.raswpai-form');
			var input = container.querySelector('#raswpai-input');
			var isRTL = !!(window.raswpaiChat && raswpaiChat.rtl);
			var attrs = (window.raswpaiChat && raswpaiChat.attrs) || {};
			var ui = (window.raswpaiChat && raswpaiChat.ui) || {};
			var restUrl = (window.raswpaiChat && raswpaiChat.restUrl) || '';
			var nonce = (window.raswpaiChat && raswpaiChat.nonce) || '';
			var sessionId = localStorage.getItem('raswpai_session_id') || '';

			// Theme handling
			var theme = ui.theme || 'auto';
			var root = container.closest('html') || document.documentElement;
			function applyTheme(){
				if(theme === 'dark'){ root.classList.add('dark'); }
				else if(theme === 'light'){ root.classList.remove('dark'); }
				else {
					var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
					root.classList.toggle('dark', !!prefersDark);
				}
			}
			applyTheme();

			function appendMessage(role, text){
				var wrapper = el('div', 'flex ' + (role==='user' ? (isRTL ? 'justify-start' : 'justify-end') : (isRTL ? 'justify-end' : 'justify-start')));
				var bubble = el('div', 'max-w-[85%] px-3 py-2 rounded-lg text-sm whitespace-pre-wrap break-words');
				if(role==='user'){
					bubble.classList.add('bg-indigo-600','text-white');
				}else{
					bubble.classList.add('bg-gray-100','dark:bg-gray-900','dark:text-gray-100');
				}
				bubble.textContent = text;
				wrapper.appendChild(bubble);
				messagesEl.appendChild(wrapper);
				messagesEl.scrollTop = messagesEl.scrollHeight;
			}

			function appendLoading(){
				var wrapper = el('div', 'flex ' + (isRTL ? 'justify-end' : 'justify-start'));
				var bubble = el('div', 'max-w-[85%] px-3 py-2 rounded-lg text-sm bg-gray-100 dark:bg-gray-900 dark:text-gray-100');
				bubble.setAttribute('role','status');
				bubble.textContent = '…';
				wrapper.appendChild(bubble);
				messagesEl.appendChild(wrapper);
				messagesEl.scrollTop = messagesEl.scrollHeight;
				return wrapper;
			}

			if (ui && ui.intro) {
				appendMessage('assistant', (typeof ui.intro === 'string') ? ui.intro.replace(/<[^>]+>/g,'') : '');
			}

			form.addEventListener('submit', function(e){
				e.preventDefault();
				var text = (input.value || '').trim();
				if(!text){ input.focus(); return; }
				appendMessage('user', text);
				input.value = '';
				input.setAttribute('aria-busy','true');
				var loadingNode = appendLoading();

				fetch(restUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({
						message: text,
						session_id: sessionId,
						nonce: nonce,
						attrs: attrs
					})
				}).then(function(res){ return res.json(); })
				.then(function(json){
					if(json && json.session_id){ sessionId = json.session_id; localStorage.setItem('raswpai_session_id', sessionId); }
					if(json && json.message){ appendMessage('assistant', String(json.message)); }
					else { appendMessage('assistant', ''); }
				})
				.catch(function(err){ appendMessage('assistant', 'Error'); })
				.finally(function(){
					if(loadingNode && loadingNode.parentNode){ loadingNode.parentNode.remove(); }
					input.removeAttribute('aria-busy');
					input.focus();
				});
			});
		});
	});
})();