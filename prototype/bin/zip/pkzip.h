#ifndef PKZIP_H
#define PKZIP_H

#include <iostream>
#include <stdexcept>
#include <fstream>
#include <sstream>
#include <ctime>
#include <boost/filesystem.hpp>
#include <zlib.h>

class PKZip
{
	public:
		// Signatures: (4 bytes)
		static const unsigned int SIGNATURE_CENTRAL_FILE;
		static const unsigned int SIGNATURE_LOCAL_FILE;
		static const unsigned int SIGNATURE_DIGITAL;
		static const unsigned int SIGNATURE_END_RECORD;
		static const unsigned int SIGNATURE_ZIP64_END_RECORD;
		static const unsigned int SIGNATURE_ZIP64_END_LOCATOR;
		static const unsigned int SIGNATURE_ARCHIVE_EXTRA_DATA;

		// Version made by (2 bytes)
		static const unsigned short VERSION_MADE_MSDOS;
		static const unsigned short VERSION_MADE_AMIGA;
		static const unsigned short VERSION_MADE_OPENVMS;
		static const unsigned short VERSION_MADE_UNIX;
		static const unsigned short VERSION_MADE_VMCMS;
		static const unsigned short VERSION_MADE_ATARI;
		static const unsigned short VERSION_MADE_OS2;
		static const unsigned short VERSION_MADE_MACINTOSH;
		static const unsigned short VERSION_MADE_ZSYSTEM;
		static const unsigned short VERSION_MADE_CPM;
		static const unsigned short VERSION_MADE_WINDOWS_NTFS;
		static const unsigned short VERSION_MADE_MVS;
		static const unsigned short VERSION_MADE_VSE;
		static const unsigned short VERSION_MADE_ACORN_RISC;
		static const unsigned short VERSION_MADE_VFAT;
		static const unsigned short VERSION_MADE_ALTERNATE_MVS;
		static const unsigned short VERSION_MADE_BEOS;
		static const unsigned short VERSION_MADE_TANDEM;
		static const unsigned short VERSION_MADE_OS400;
		static const unsigned short VERSION_MADE_OSX;

		// Current minimum feature versions: (2 bytes)
		static const unsigned short VERSION_1_0;
		static const unsigned short VERSION_1_1;
		static const unsigned short VERSION_2_0;
		static const unsigned short VERSION_2_1;
		static const unsigned short VERSION_2_5;
		static const unsigned short VERSION_2_7;
		static const unsigned short VERSION_4_5;
		static const unsigned short VERSION_4_6;
		static const unsigned short VERSION_5_0;
		static const unsigned short VERSION_5_1;
		static const unsigned short VERSION_5_2;
		static const unsigned short VERSION_6_1;
		static const unsigned short VERSION_6_2;
		static const unsigned short VERSION_6_3;

		// General purpose bit flag: (2 bytes)
		static const unsigned short FLAG_NONE;
		static const unsigned short FLAG_ENCRYPTED;
		static const unsigned short FLAG_COMPRESS_OPT1;
		static const unsigned short FLAG_COMPRESS_OPT2;
		static const unsigned short FLAG_DATA_DESCRIPTOR;
		static const unsigned short FLAG_ENHANCED_DEFLATION;
		static const unsigned short FLAG_COMPRESSED_PATCHED_DATA;
		static const unsigned short FLAG_STRONG_ENCRYPTION;
		static const unsigned short FLAG_UNUSED1;
		static const unsigned short FLAG_UNUSED2;
		static const unsigned short FLAG_UNUSED3;
		static const unsigned short FLAG_UNUSED4;
		static const unsigned short FLAG_LANG_ENCODING;
		static const unsigned short FLAG_RESERVED1;
		static const unsigned short FLAG_MASK_HEADER_VALUES;
		static const unsigned short FLAG_RESERVED2;
		static const unsigned short FLAG_RESERVED3;

		// Compression method: (2 bytes)
		static const unsigned short CMETHOD_STORED;
		static const unsigned short CMETHOD_SHRUNK;
		static const unsigned short CMETHOD_REDUCED1;
		static const unsigned short CMETHOD_REDUCED2;
		static const unsigned short CMETHOD_REDUCED3;
		static const unsigned short CMETHOD_REDUCED4;
		static const unsigned short CMETHOD_IMPLODED;
		static const unsigned short CMETHOD_RESERVED_TOKENIZING;
		static const unsigned short CMETHOD_DEFLATED;
		static const unsigned short CMETHOD_DEFLATE64;
		static const unsigned short CMETHOD_PKWARE_IMPLODING;
		static const unsigned short CMETHOD_RESERVED1;
		static const unsigned short CMETHOD_BZIP2;
		static const unsigned short CMETHOD_RESERVED2;
		static const unsigned short CMETHOD_LZMA;
		static const unsigned short CMETHOD_RESERVED3;
		static const unsigned short CMETHOD_RESERVED4;
		static const unsigned short CMETHOD_RESERVED5;
		static const unsigned short CMETHOD_IBM_TERSE;
		static const unsigned short CMETHOD_IBM_LZ77;
		static const unsigned short CMETHOD_WAVPACK;
		static const unsigned short CMETHOD_PPMD;

		// Initial DOS date (1980-01-01 00:00:00) in unix timestamp
		static const unsigned int DOS_TIME_INIT;

		static const unsigned int CHUNK;

		PKZip( std::string );
		PKZip* setZipFile( std::string );
		PKZip* setStatusFile( std::string );
		PKZip* setComment( std::string );
		PKZip* setTotalUncompressSize( unsigned long int );
		bool addFile( std::string, std::string );
		bool close();
		~PKZip();

	private:
		std::ofstream*         _zfile;
		std::ofstream*         _sfile;
		std::stringstream*     _ctrl_dir;
		std::string            _zdir;
		std::string            _zip_comment;
		unsigned int           _offset;
		unsigned short         _ctrl_dir_cnt;
		unsigned short         _level;
		unsigned long int      _total_size;
		unsigned long int      _proc_size;
		unsigned short         _proc_perc;
		bool                   _closed;

		time_t _unix2DosTime( time_t );
		bool _gzcompress( std::string, unsigned int&, unsigned long& );
		bool _writeCentralSection();
		bool _setProgress( unsigned long int );
		PKZip* _xField_Timestamp( std::stringstream&, bool, time_t = 0, time_t = 0, time_t = 0 );
		PKZip* _xField_UnixOwner( std::stringstream&, unsigned short = 4, unsigned int = 1000, unsigned int= 1000 );

};

#endif
