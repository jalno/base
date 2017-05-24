const webpack = require("webpack");
const path = require('path');
const ExtractTextPlugin = require("extract-text-webpack-plugin");
const CleanCSSPlugin = require("less-plugin-clean-css");
const extractText = new ExtractTextPlugin({
    filename: "[name].css"
});
const entries = require('./webpack.entries.json');
var commonFiles = [];
function isExternal(module) {
	const userRequest = module.userRequest;

	if (typeof userRequest !== 'string') {
		return false;
	}
	let found = false;
	let exts = ['.ts', '.js'];

	exts = exts.sort();
	for(var i = 0;i < exts.length && !found;i++){
		if(userRequest.substr(-exts[i].length) == exts[i]){
			found = true;
		}
	}
	if(!found){
		return false;
	}
	if(commonFiles.indexOf(userRequest) >= 0){
		return true;
	}
	commonFiles.push(userRequest);
	const node_modules = path.resolve(__dirname, 'node_modules');
	return (userRequest.substr(0, node_modules.length) == node_modules);
}

module.exports = {
	entry: entries,
	output: {
		filename: '[name].js',
		chunkFilename: '[name].js',
		path: path.resolve(__dirname, "../../public/frontend/dist/")
	},
	resolve: {
		extensions: ['.ts', '.js',".less", ".css"]
	},
	module: {
		rules: [
			{test: /\.less$/,use: extractText.extract({use: [{
				loader: "css-loader",
				options: {
					minimize: true
				}
			}, {
				loader: "less-loader",
				options: {
					paths: [
                        __dirname
                    ],
                    plugins: [
                        new CleanCSSPlugin({ advanced: true })
                    ]
                }
			}]})},
			{test: /\.css/,use: extractText.extract({use: [
				{
					loader: "css-loader",
					options: {
						minimize: true
					}
				}
			]})},
			{ test: /\.json$/,loader: "json-loader" },
			{ test: /\.png$/,loader: "file-loader" },
			{ test: /\.jpg$/,loader: "file-loader" },
			{ test: /\.gif$/,loader: "file-loader" },
			{ test: /\.woff2?$/,loader: "file-loader" },
			{ test: /\.eot$/,loader: "file-loader" },
			{ test: /\.ttf$/,loader: "file-loader" },
			{ test: /\.svg$/,loader: "file-loader" },
			{ 
				test: /\.tsx?$/,
				loader: "ts-loader",
				options:{
					transpileOnly: true,
					logLevel:'warn'
				}
	 		}
		]
	},
	plugins:[
		extractText,
		new webpack.optimize.UglifyJsPlugin({
			minimize: true,
			output:{
				comments:false
			}
		}),
		new webpack.optimize.CommonsChunkPlugin({
            name: "common",
            minChunks: function(module) {
				return isExternal(module);
			}
        }),
		new webpack.ProvidePlugin({
			$: "jquery",
			jQuery: "jquery",
			"window.jQuery":"jquery"
		})
	]

};