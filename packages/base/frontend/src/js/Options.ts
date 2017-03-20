declare var options: any;
export class Options{
	public static set(key :string, value:any){
		options[key] = value;
	}
	public static get(key:string):any{
		return options[key];
	}
}