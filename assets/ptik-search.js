(function () {
	'use strict';

	function debounce( fn, wait ) {
		var t;
		return function () {
			var args = arguments;
			var ctx = this;
			clearTimeout( t );
			t = setTimeout( function () {
				fn.apply( ctx, args );
			}, wait );
		};
	}

	var doSearch = debounce( function ( form, input, resultBox ) {
		resultBox.innerHTML = '<ul><li><div class="loading_search"></div></li></ul>';

		var fd = new FormData();
		fd.append( 's', input.value.trim() );
		fd.append( 'source', form.dataset.source || '' );
		fd.append( 'taxonomy', form.dataset.taxonomy || '' );
		fd.append( 'image', form.dataset.image || 'false' );
		fd.append( 'price', form.dataset.price || 'false' );
		fd.append( 'count', form.dataset.count || '5' );
		fd.append( 'depth', form.dataset.depth || '0' );
		fd.append( 'action', 'ptik_search' );

		fetch( ptikSearchAjax.url, {
			method: 'POST',
			body: fd,
		} )
			.then( function ( res ) {
				return res.text();
			} )
			.then( function ( html ) {
				resultBox.innerHTML = html && html.trim().length
					? html
					: '<ul><li>محتوایی یافت نشد ...</li></ul>';
			} )
			.catch( function () {
				resultBox.innerHTML = '<ul><li>خطا در دریافت اطلاعات، دوباره تلاش کنید</li></ul>';
			} );
	}, 350 );

	document.addEventListener( 'input', function ( e ) {
		if ( ! e.target.classList.contains( 'input_search' ) ) {
			return;
		}
		var input = e.target;
		var form = input.closest( 'form.ptik-form' );
		if ( ! form ) {
			return;
		}
		var resultBox = form.parentElement.querySelector( '.search_result' );
		var value = input.value.trim();

		if ( value.length === 0 ) {
			resultBox.style.display = 'none';
			return;
		}
		resultBox.style.display = 'block';
		doSearch( form, input, resultBox );
	} );

	// بستن باکس نتایج با کلیک بیرون از باکس جستجو
	document.addEventListener( 'click', function ( e ) {
		document.querySelectorAll( '.search_result' ).forEach( function ( box ) {
			var wrapper = box.closest( '.search_bar' );
			if ( wrapper && ! wrapper.contains( e.target ) ) {
				box.style.display = 'none';
			}
		} );
	} );
} )();

/* اضافه‌کردن/حذف کلاس is-filled برای استایل madoka — بدون وابستگی به کتابخونه‌ی classie
 * (نسخه‌ی سازگارشده با کلاس‌های خود این افزونه، معادل اسکریپت اصلی دموی tympanus.net) */
(function () {
	document.querySelectorAll( '.ptik-input--madoka' ).forEach( function ( inputEl ) {
		var wrap = inputEl.closest( '.ptik-input-wrap--madoka' );
		if ( ! wrap ) {
			return;
		}
		if ( inputEl.value.trim() !== '' ) {
			wrap.classList.add( 'is-filled' );
		}
		inputEl.addEventListener( 'focus', function () {
			wrap.classList.add( 'is-filled' );
		} );
		inputEl.addEventListener( 'blur', function () {
			if ( inputEl.value.trim() === '' ) {
				wrap.classList.remove( 'is-filled' );
			}
		} );
	} );
})();

/* هم‌اندازه کردن viewBox با ابعاد واقعی کادر برای استایل madoka.
 * دموی اصلی یک viewBox باریک (404x77) دارد که با preserveAspectRatio="none" کشیده می‌شود؛
 * روی یک کادر جست‌وجوی تمام‌عرض این کشش نامتقارن باعث می‌شود لبه‌های عمودی چند برابر
 * کلفت‌تر از لبه‌های افقی رسم شوند. با هم‌اندازه کردن viewBox، ضخامت همه‌جا یکسان می‌ماند.
 * pathLength هم مقدار dasharray/dashoffset را مستقل از ابعاد واقعی نگه می‌دارد. */
(function () {
	function syncMadokaBox( svg ) {
		var rect = svg.getBoundingClientRect();
		var w = Math.round( rect.width );
		var h = Math.round( rect.height );
		if ( ! w || ! h ) {
			return;
		}
		var path = svg.querySelector( 'path' );
		if ( ! path ) {
			return;
		}
		svg.setAttribute( 'viewBox', '0 0 ' + w + ' ' + h );
		path.setAttribute( 'd', 'm0,0l' + w + ',0l0,' + h + 'l-' + w + ',0l0,-' + h + 'z' );
		path.setAttribute( 'pathLength', '962' );
	}

	function syncAll() {
		document.querySelectorAll( '.ptik-graphic--madoka' ).forEach( syncMadokaBox );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', syncAll );
	} else {
		syncAll();
	}
	window.addEventListener( 'load', syncAll );

	var resizeTimer;
	window.addEventListener( 'resize', function () {
		clearTimeout( resizeTimer );
		resizeTimer = setTimeout( syncAll, 150 );
	} );
})();
