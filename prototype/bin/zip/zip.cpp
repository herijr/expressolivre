#include <iostream>
#include <fstream>
#include <stdexcept>
#include <boost/filesystem.hpp>
#include <queue>

#include "pkzip.h"

using namespace std;

int main( int argc, char* argv[] ) {
	try {
		if ( argc < 2 ) throw runtime_error( "no directory" );

		boost::filesystem::path zdir( argv[1] );

		boost::filesystem::path zfile( zdir.string()+"/messages.zip" );
		boost::filesystem::path sfile( zdir.string()+"/.zipstat" );

		boost::uintmax_t sum = 0;
		queue<string> file_list;

		PKZip zip( zdir.string() );
		zip.setZipFile( zfile.string() );
		zip.setStatusFile( sfile.string() );

		for ( boost::filesystem::recursive_directory_iterator end, dir( zdir ); dir != end; ++dir ) {

			// Skip directories
			if ( !boost::filesystem::is_regular_file( *dir ) ) continue;

			// Skip target files
			if ( dir->path().string() == zfile.string() || dir->path().string() == sfile.string() ) continue;

			// Get sum of uncompress files
			sum += boost::filesystem::file_size( *dir );

			// Get relative path
			file_list.push( dir->path().string().substr( zdir.string().length() + 1 ) );
		}

		if ( file_list.empty() ) throw runtime_error( "empty" );

		zip.setTotalUncompressSize( sum );

		while ( !file_list.empty() ) {

			zip.addFile( file_list.front(), "" );

			//break;
			file_list.pop();
		}

	} catch( const exception & e ) {
		return -1;
	}
	return 0;
}

