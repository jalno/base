import {Options} from "./Options";
import {Translator} from "./Translator";
export class Router{
	private static http_build_query (formdata:any, numericPrefix?:string, argSeparator?:string):string{
		let _httpBuildQueryHelper = function (key:string, val:any, argSeparator:string):string{
			let k;
			let tmp = [];
			if (val === true) {
				val = '1'
			} else if (val === false) {
				val = '0'
			}
			if (val !== null) {
				if (typeof val === 'object') {
					for (k in val) {
						if (val[k] !== null) {
							tmp.push(_httpBuildQueryHelper(key + '[' + k + ']', val[k], argSeparator))
						}
					}
					return tmp.join(argSeparator);
				} else if (typeof val !== 'function') {
					return encodeURIComponent(key) + '=' + encodeURIComponent(val)
				} else {
					throw new Error('There was an error processing for http_build_query().')
				}
			}
			return '';
		}

		if (!argSeparator) {
			argSeparator = '&'
		}
		let tmp :string[] = [];
		for (let key in formdata) {
			let value = formdata[key]
			if (numericPrefix) {
				key = String(numericPrefix) + key;
			}
			var query = _httpBuildQueryHelper(key, value, argSeparator);
			if (query !== '') {
				tmp.push(query);
			}
		}
		return tmp.join(argSeparator);
	}

	public static url (page?:string, parameters?:{lang?:string, hostname?:string}, absolute?:boolean):string{
		if(parameters == undefined){
			parameters = {};
		}
		if(absolute == undefined){
			absolute = false;
		}
		let lastSlash:string = Options.get('packages.base.routing.lastslash');
		if(lastSlash){
			if(page.substr(-1) != '/'){
				page += '/';
			}
		}else{
			while(page.substr(-1) == '/'){
				page = page.substr(0, length - 1);
			}
		}
		var url = '';
		if(absolute == true){
			var hostname = window.location.hostname;
			if(parameters.hasOwnProperty('hostname')){
				hostname = parameters.hostname;
				delete parameters.hostname;
			}
			url += window.location.protocol+'//'+hostname;
		}

		var changelang = Options.get('packages.base.translator.changelang');
		var type = Options.get('packages.base.translator.changelang.type');
		if(changelang == 'uri'){
			var lang = '';
			if(parameters.hasOwnProperty('lang')){
				lang = parameters.lang;
				delete parameters.lang;
			}else{
				if(type == 'short'){
					lang = Translator.getShortCodeLang();
				}else if(type == 'complete'){
					lang = Translator.getCodeLang();
				}
			}
			if(!page){
				if(lang.length == 2){
					if(lang != Translator.getDefaultShortLang()){
						url += '/'+lang;
					}
				}else if(lang && lang != Translator.getDefaultLang()){
					url += '/'+lang;
				}
			}else if(lang){
				url += '/'+lang;
			}
		}else if(changelang == 'parameter'){
			if(!parameters.hasOwnProperty('lang')){
				if(type == 'short'){
					parameters.lang = Translator.getShortCodeLang();
				}else if(type == 'complete'){
					parameters.lang = Translator.getCodeLang();
				}
			}
		}
		if(page){
			url += '/'+page;
		}
		if(!url){
			url += '/';
		}
		let query = this.http_build_query(parameters);
		if(query.length > 0){
			url += '?'+query;
		}
		return url;
	}
}